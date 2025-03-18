<?php
/*
 * SIMP - Spam identification Management Protocol.
 */

class Simp {
  
  public $redis     = false;
  public $ip        = false;
  public $group     = false;
  public $is_spam   = false;
  public $threshold = 5;  // This can be set to whatever is deemed best.
  public $window    = 10; // This can be set to whatever is deemed best.
  
  function __construct() {
    $this->set_ip_and_group();
    $this->redis();
    $this->track_ip();
    $this->spam_kill();
  }
  
  private function set_ip_and_group() {
    $this->ip = $_SERVER['REMOTE_ADDR'];
    $this->get_ip_group($this->ip); // The 'group' is the first three blocks in the IP address.
  }
  
  private function redis() {
    try {
      $redis = new Redis();
      $redis->pconnect('127.0.0.1', 6379); // Persistent connection so that memory is not overwhelmed.
      $redis->auth('password');
      $this->redis = $redis;
    } catch (Exception $e) {
      $this->log("Redis error: " . $e->getMessage() . "\n");
    }
  }
  
  private function track_ip() {
    if ($this->redis) {
      $is_spam = false;
      $key = 'simp:group:' . $this->group;
      $ttl = 1209600; // 14 days in seconds so that we can view redis data if needed for history.
      $max_requests = $this->threshold;
      $time_window = $this->window;
  
      $current_time = microtime(true); // High-resolution timestamp (seconds + microseconds)
  
      $this->redis->pipeline(function ($pipe) use ($key, $current_time, $max_requests, $ttl) {
        $pipe->lpush($key, $current_time);
        $pipe->ltrim($key, 0, $max_requests - 1);
        $pipe->expire($key, $ttl);
      })->exec();
  
      $timestamps = $this->redis->lrange($key, 0, -1);
  
      if (count($timestamps) === $max_requests) {
        $newest = (float) $timestamps[0]; // First entry (most recent)
        $oldest = (float) $timestamps[$max_requests - 1]; // Last entry (oldest)
        $time_difference = $newest - $oldest;
        if ($time_difference <= $time_window) {
          $is_spam = true;
      
        }
      }
  
      $this->is_spam = $is_spam;
    } else {
      return; // Redis not loaded.
    }
  }
  
  private function spam_kill() {
    if($this->is_spam) {
      /*
       * Rather than give hints to the spam bot that we 'caught' them, I'd suggest we make them think the site is down.
       * This may not work at all but it is better then a message that indicates they should start a work around.
       */
      http_response_code(503);
      header('Retry-After: 3600');
      echo "Service Temporarily Unavailable";
      exit;
    }
  }
  
  /*
   * Helpers
   */
  
  private function get_ip_group($ip) {
    $array = explode('.', $ip);
    unset($array[3]); // Drop the last part of the ip address.
    return implode('.', $array);
  }
  
  private function log($message) {
    // This method of logging may still work on Acquia if I'm not mistaken.
    if(!empty($message)) {
      file_put_contents('php://stderr', $message, FILE_APPEND);
    }
  }
  
}
