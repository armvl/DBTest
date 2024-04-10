<?php

namespace FpDbTest;

use Exception;
use mysqli;

class Database implements DatabaseInterface {
  private mysqli $mysqli;
  private string $skip = '__skip';

  public function __construct (mysqli $mysqli) {
    $this->mysqli = $mysqli;
  }

  public function skip () {
    return $this->skip;
  }

  public function buildQuery (string $query, array $args = []): string {
    if (preg_match_all('/\?[dfa#]?/', $query) !== count($args))
      throw new Exception('Arguments dont match');

    $sql = preg_replace_callback('/\?[dfa#]?/', function ($matches) use ( & $args) {
      $specifier = $matches[0];
      $value = array_shift($args);

      return $value === $this->skip
        ? $this->skip
        : match ($specifier) {
          '?' => $this->specifierDefault($value),
          '?d' => $this->specifierInt($value),
          '?f' => $this->specifierFloat($value),
          '?a' => $this->specifierArray($value),
          '?#' => $this->specifierIdentifier($value),
        }
      ;
    }, $query);

    return preg_replace_callback('/\{[^}]+\}/', function ($matches) {
      $clean = trim($matches[0], '{}');

      if (str_contains($clean, '{'))
        throw new Exception('Nested conditional block detected');

      return str_contains($clean, $this->skip) ? '' : $clean;
    }, $sql);
  }

  private function specifierDefault (float|bool|int|string|null $value) {
    return match (true) {
      is_null($value) => 'NULL',
      is_string($value) => "'".$this->mysqli->real_escape_string($value)."'",
      is_bool($value) => intval($value),
      default => $value,
    };
  }

  private function specifierInt ($value) {
    return is_null($value) ? 'NULL' : intval($value);
  }

  private function specifierFloat ($value) {
    return is_null($value) ? 'NULL' : floatval($value);
  }

  private function specifierArray (array $value): string {
    if (array_is_list($value)) {
      $value = array_map(fn ($val) => $this->specifierDefault($val), $value);
    }
    else {
      $value = array_map(function ($key, $val) {
        return $this->specifierIdentifier($key).' = '.$this->specifierDefault($val);
      }, array_keys($value), $value);
    }

    return join(', ', $value);
  }

  private function specifierIdentifier (string|array $value): string {
    $value = array_map(fn ($val) => $this->mysqli->real_escape_string($val), (array) $value);
    return '`'.join('`, `', $value).'`';
  }
}
