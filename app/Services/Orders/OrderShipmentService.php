<?php

namespace App\Services\Orders;

use App\Models\Order;

/**
 * Shipment tracking is manually maintained for now: staff type in a
 * carrier and tracking number/URL by hand. There is no carrier API
 * integration — nothing here implies a live tracking feed.
 */
class OrderShipmentService
{
    /**
     * @throws OrderShipmentException
     */
    public function update(Order $order, ?string $carrier, ?string $trackingNumber, ?string $trackingUrl): Order
    {
        if ($trackingUrl !== null && ! $this->isHttpsUrl($trackingUrl)) {
            throw new OrderShipmentException('Tracking URL must be a valid https:// URL.');
        }

        $order->update([
            'carrier' => $carrier,
            'tracking_number' => $trackingNumber,
            'tracking_url' => $trackingUrl,
        ]);

        return $order->fresh();
    }

    private function isHttpsUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false && str_starts_with($url, 'https://');
    }
}
