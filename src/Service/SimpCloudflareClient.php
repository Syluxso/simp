<?php

namespace Drupal\simp\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;

class SimpCloudflareClient {

  protected $httpClient;
  protected $config;

  public function __construct(ClientInterface $http_client, ConfigFactoryInterface $config_factory) {
    $this->httpClient = $http_client;
    $this->config = $config_factory->get('simp.settings');
  }

  /**
   * Ban an IP address via Cloudflare.
   */
  public function banIp(string $ip): bool {
    $api_key = $this->config->get('cloudflare_api_key');
    $email = $this->config->get('cloudflare_email');
    $zone_id = $this->config->get('cloudflare_zone_id');

    try {
      $response = $this->httpClient->post("https://api.cloudflare.com/client/v4/zones/{$zone_id}/firewall/access_rules/rules", [
        'headers' => [
          'X-Auth-Email' => $email,
          'X-Auth-Key' => $api_key,
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'mode' => 'block',
          'configuration' => [
            'target' => 'ip',
            'value' => $ip,
          ],
          'notes' => 'Blocked by SIMP module due to spam detection',
        ],
      ]);

      return $response->getStatusCode() === 200;
    } catch (\Exception $e) {
      return FALSE;
    }
  }
}
