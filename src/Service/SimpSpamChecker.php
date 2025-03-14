<?php

namespace Drupal\simp\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\redis\ClientFactory;
use Predis\Client;

class SimpSpamChecker {

  protected $redis;
  protected $config;

  public function __construct(ClientFactory $redis_factory, ConfigFactoryInterface $config_factory) {
    $this->redis = $redis_factory->getClient();
    $this->config = $config_factory->get('simp.settings');
  }

  /**
   * Track an IP address using a fixed-size list of timestamps and decide if it should be banned.
   *
   * @param string $ip
   *   The IP address to track.
   *
   * @return bool
   *   TRUE if the IP should be flagged as spam, FALSE otherwise.
   */
  public function trackIp(string $ip): bool {
    $is_spam = false;
    $key = "simp:ip:{$ip}";
    $ttl = 1209600; // 14 days in seconds so that we can view redis data if needed for history.
    $max_requests = $this->config->get('max_requests') ?? 5;
    $time_window = $this->config->get('time_window') ?? 10; // Default to 10 seconds

    $current_time = microtime(true); // High-resolution timestamp (seconds + microseconds)

    $this->redis->pipeline(function ($pipe) use ($key, $current_time, $max_requests, $ttl) {
      $pipe->lpush($key, $current_time); // Add new timestamp to the front of the list
      $pipe->ltrim($key, 0, $max_requests - 1); // Keep only the most recent $max_requests entries
      $pipe->expire($key, $ttl); // Set TTL for the list
    });

    $timestamps = $this->redis->lrange($key, 0, -1);

    if (count($timestamps) === $max_requests) {
      $newest = (float) $timestamps[0]; // First entry (most recent)
      $oldest = (float) $timestamps[$max_requests - 1]; // Last entry (oldest)
      $time_difference = $newest - $oldest;
      if ($time_difference <= $time_window) {
        $is_spam = true;
        \Drupal::logger('simp')->warning('IP @ip flagged as spam: @count requests in @time seconds.', [
          '@ip' => $ip,
          '@count' => $max_requests,
          '@time' => round($time_difference, 2),
        ]);
      }
    }

    return $is_spam;
  }
}
