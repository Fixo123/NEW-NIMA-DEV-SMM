<?php
// Small wrapper around the SMM API provider's endpoint.
// Works with the common "Perfect Panel style" API used by most providers
// (AmazingSMM, JustAnotherPanel, and many others all follow this same shape).

require_once __DIR__ . '/config.php';

/**
 * Send a POST request to the API provider.
 */
function smm_api_request(array $params): array
{
    $params['key'] = SMM_API_KEY;

    $ch = curl_init(SMM_API_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['error' => 'Connection failed: ' . $error];
    }

    $decoded = json_decode($response, true);

    if ($decoded === null) {
        return ['error' => 'Invalid response from provider'];
    }

    return $decoded;
}

/**
 * Place an order with the provider.
 * Returns ['order' => <api_order_id>] on success, or ['error' => <message>] on failure.
 */
function smm_place_order(int $providerServiceId, string $link, int $quantity): array
{
    return smm_api_request([
        'action'  => 'add',
        'service' => $providerServiceId,
        'link'    => $link,
        'quantity'=> $quantity,
    ]);
}

/**
 * Check the status of an order already placed with the provider.
 */
function smm_order_status(string $apiOrderId): array
{
    return smm_api_request([
        'action' => 'status',
        'order'  => $apiOrderId,
    ]);
}

/**
 * Pull the provider's full service list (useful for an admin "sync services" tool later).
 */
function smm_service_list(): array
{
    return smm_api_request(['action' => 'services']);
}
