<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Factura extends Model
{
    protected $fillable = [
        'restaurante_id',
        'serie_facturacion_id',
        'orden_id',
        'comercio_fiscal_id',
        'numero_factura',
        'numero_serie',
        'fecha_emision',
        'fecha_operacion',
        'tipo_factura',
        'factura_rectificada_id',
        'motivo_rectificacion',
        'base_imponible',
        'total_iva',
        'total_recargo',
        'total_factura',
        'desglose_iva',
        'descripcion',
        'observaciones',
        'estado',
        'fecha_envio_verifactu',
        'verifactu_id',
        'verifactu_qr_url',
        'verifactu_qr_data',
        'verifactu_response',
        'verifactu_error',
        'aeat_estado',
        'aeat_response',
        'aeat_fecha_respuesta',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_operacion' => 'date',
        'fecha_envio_verifactu' => 'datetime',
        'aeat_fecha_respuesta' => 'datetime',
        'base_imponible' => 'decimal:2',
        'total_iva' => 'decimal:2',
        'total_recargo' => 'decimal:2',
        'total_factura' => 'decimal:2',
        'desglose_iva' => 'array',
        'verifactu_response' => 'array',
        'aeat_response' => 'array',
    ];

    /**
     * Relación con Restaurante
     */
    public function restaurante(): BelongsTo
    {
        return $this->belongsTo(Restaurante::class);
    }

    /**
     * Relación con Serie de Facturación
     */
    public function serieFacturacion(): BelongsTo
    {
        return $this->belongsTo(SerieFacturacion::class);
    }

    /**
     * Relación con Orden (pedido)
     */
    public function orden(): BelongsTo
    {
        return $this->belongsTo(Orden::class);
    }

    /**
     * Relación con Comercio Fiscal (cliente)
     */
    public function comercioFiscal(): BelongsTo
    {
        return $this->belongsTo(ComercioFiscal::class);
    }

    /**
     * Relación con Factura Rectificada
     */
    public function facturaRectificada(): BelongsTo
    {
        return $this->belongsTo(Factura::class, 'factura_rectificada_id');
    }

    /**
     * Relación con Facturas Rectificativas (que rectifican esta factura)
     */
    public function facturasRectificativas(): HasMany
    {
        return $this->hasMany(Factura::class, 'factura_rectificada_id');
    }

    /**
     * Relación con Líneas de Factura
     */
    public function lineas(): HasMany
    {
        return $this->hasMany(FacturaLinea::class)->orderBy('orden');
    }

    /**
     * Calcular totales de la factura a partir de sus líneas
     */
    public function calcularTotales(): void
    {
        $this->lineas->load('producto');

        $baseImponible = 0;
        $totalIva = 0;
        $totalRecargo = 0;
        $desgloseIva = [];

        foreach ($this->lineas as $linea) {
            $baseImponible += $linea->base_imponible;
            $totalIva += $linea->cuota_iva;
            $totalRecargo += $linea->cuota_recargo;

            // Agrupar por tipo de IVA para el desglose
            $tipoIva = (string) $linea->tipo_iva;
            if (!isset($desgloseIva[$tipoIva])) {
                $desgloseIva[$tipoIva] = [
                    'tipo_iva' => $linea->tipo_iva,
                    'base' => 0,
                    'cuota' => 0,
                ];
            }

            $desgloseIva[$tipoIva]['base'] += $linea->base_imponible;
            $desgloseIva[$tipoIva]['cuota'] += $linea->cuota_iva;
        }

        $this->base_imponible = round($baseImponible, 2);
        $this->total_iva = round($totalIva, 2);
        $this->total_recargo = round($totalRecargo, 2);
        $this->total_factura = round($baseImponible + $totalIva + $totalRecargo, 2);
        $this->desglose_iva = array_values($desgloseIva);
    }

    /**
     * Marcar factura como emitida
     */
    public function marcarComoEmitida(): void
    {
        $this->estado = 'emitida';
        $this->save();
    }

    /**
     * Marcar factura como enviada a VeriFactu
     */
    public function marcarComoEnviada(string $verifactuId, ?array $response = null, ?string $qrUrl = null, ?string $qrData = null): void
    {
        $this->estado = 'enviada';
        $this->fecha_envio_verifactu = now();
        $this->verifactu_id = $verifactuId;
        $this->verifactu_qr_url = $qrUrl;
        $this->verifactu_qr_data = $qrData;
        $this->verifactu_response = $response;
        $this->aeat_estado = 'pendiente';
        $this->save();
    }

    /**
     * Marcar factura como anulada
     */
    public function anular(?string $motivo = null): void
    {
        $this->estado = 'anulada';
        if ($motivo) {
            $this->observaciones = ($this->observaciones ? $this->observaciones . "\n\n" : '') .
                                  "ANULADA: {$motivo}";
        }
        $this->save();
    }

    /**
     * Registrar error de VeriFactu
     */
    public function registrarErrorVeriFactu(string $error): void
    {
        $this->verifactu_error = $error;
        $this->save();
    }

    /**
     * Verificar si es una factura rectificativa
     */
    public function esRectificativa(): bool
    {
        return in_array($this->tipo_factura, ['R1', 'R2', 'R3', 'R4', 'R5']);
    }

    /**
     * Verificar si es una factura simplificada
     */
    public function esSimplificada(): bool
    {
        return in_array($this->tipo_factura, ['F2', 'F3']);
    }

    /**
     * Verificar si puede ser enviada a VeriFactu
     */
    public function puedeEnviarse(): bool
    {
        return $this->estado === 'emitida' &&
               $this->total_factura > 0 &&
               $this->lineas()->count() > 0;
    }

    /**
     * Verificar si ha sido enviada a VeriFactu
     */
    public function estaEnviada(): bool
    {
        return $this->estado === 'enviada' && !empty($this->verifactu_id);
    }

    /**
     * Obtener el número de factura formateado para VeriFactu
     */
    public function getNumeroFacturaVeriFactuAttribute(): string
    {
        return $this->numero_factura;
    }

    /**
     * Obtener fecha de emisión en formato VeriFactu (DD-MM-YYYY)
     */
    public function getFechaEmisionVeriFactuAttribute(): string
    {
        return $this->fecha_emision->format('d-m-Y');
    }

    /**
     * Scope para facturas por estado
     */
    public function scopePorEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope para facturas emitidas
     */
    public function scopeEmitidas($query)
    {
        return $query->whereIn('estado', ['emitida', 'enviada']);
    }

    /**
     * Scope para facturas pendientes de envío
     */
    public function scopePendientesEnvio($query)
    {
        return $query->where('estado', 'emitida')
                     ->whereNull('verifactu_id');
    }

    /**
     * Scope para facturas de un período
     */
    public function scopePorPeriodo($query, Carbon $desde, Carbon $hasta)
    {
        return $query->whereBetween('fecha_emision', [$desde, $hasta]);
    }

    /**
     * Scope para facturas de un año fiscal
     */
    public function scopePorAnoFiscal($query, int $ano)
    {
        return $query->whereYear('fecha_emision', $ano);
    }

    /**
     * Scope para facturas de una serie
     */
    public function scopePorSerie($query, int $serieId)
    {
        return $query->where('serie_facturacion_id', $serieId);
    }

    /**
     * Marcar factura como aceptada por AEAT
     */
    public function marcarAceptadaAEAT(?array $response = null): void
    {
        $this->aeat_estado = 'aceptada';
        $this->aeat_response = $response;
        $this->aeat_fecha_respuesta = now();
        $this->save();
    }

    /**
     * Marcar factura como rechazada por AEAT
     */
    public function marcarRechazadaAEAT(string $error, ?array $response = null): void
    {
        $this->aeat_estado = 'rechazada';
        $this->aeat_response = $response;
        $this->verifactu_error = $error;
        $this->aeat_fecha_respuesta = now();
        $this->save();
    }

    /**
     * Verificar si tiene QR de VeriFactu
     */
    public function tieneQR(): bool
    {
        return !empty($this->verifactu_qr_url) || !empty($this->verifactu_qr_data);
    }

    /**
     * Verificar si fue aceptada por AEAT
     */
    public function aceptadaPorAEAT(): bool
    {
        return $this->aeat_estado === 'aceptada';
    }

    /**
     * Verificar si fue rechazada por AEAT
     */
    public function rechazadaPorAEAT(): bool
    {
        return $this->aeat_estado === 'rechazada';
    }

    /**
     * Verificar si está pendiente de respuesta AEAT
     */
    public function pendienteAEAT(): bool
    {
        return $this->aeat_estado === 'pendiente' && $this->estaEnviada();
    }
}
