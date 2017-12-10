<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 07/12/2017
 * Time: 22:48
 */

namespace Warlof\Seat\Slackbot\Repositories\Slack\Cache;


use Warlof\Seat\Slackbot\Repositories\Slack\Cache\Traits\HashesStrings;
use Warlof\Seat\Slackbot\Repositories\Slack\Configuration;
use Warlof\Seat\Slackbot\Repositories\Slack\Containers\SlackResponse;
use Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\CachePathException;

class FileCache implements CacheInterface {

    use HashesStrings;

    protected $cache_path;

    protected $results_filename = 'results.cache';

    public function __construct() {
        $this->cache_path = Configuration::getInstance()->file_cache_location;
        $this->checkCacheDirectory();
    }

    public function set(string $uri, string $query, SlackResponse $data)
    {
        $path = $this->buildRelativePath($this->safePath($uri));

        if (!file_exists($path))
            mkdir($path, 0775, true);

        if ($query != '')
            file_put_contents($path . $this->hashString($query), serialize($data));
        else
            file_put_contents($path . $this->results_filename, serialize($data));
    }

    public function get(string $uri, string $query = '')
    {
        $path = $this->buildRelativePath($this->safePath($uri));
        $cache_file_path = $path . $this->results_filename;
        if ($query != '')
            $cache_file_path = $path . $this->hashString($query);

        if (!is_readable($cache_file_path))
            return false;

        $file = unserialize(file_get_contents($cache_file_path));

        if ($file->expired()) {
            $this->forget($uri, $query);
            return false;
        }

        return $file;
    }

    public function forget(string $uri, string $query = '')
    {
        $path = $this->buildRelativePath($uri);
        $cache_file_path = $path . $this->results_filename;

        @unlink($cache_file_path);
    }

    public function has(string $uri, string $query = '') : bool
    {
        if ($status = $this->get($uri, $query))
            return true;

        return false;
    }

    private function safePath(string $uri) : string
    {
        return preg_replace('/[^A-Za-z0-9\/]/', '', $uri);
    }

    private function checkCacheDirectory() : bool
    {
        if (!is_dir($this->cache_path) && !@mkdir($this->cache_path, 0775, true))
            throw new CachePathException('Unable to create cache directory ' . $this->cache_path);

        if (!is_readable($this->cache_path) || !is_writable($this->cache_path))
            if (!chmod($this->cache_path, 0775))
                throw new CachePathException($this->cache_path . ' must be readable and writeable.');

        return true;
    }

    private function buildRelativePath(string $path) : string
    {
        return rtrim(rtrim($this->cache_path, '/') . rtrim($path), '/') . '/';
    }

}
