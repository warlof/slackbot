<?php

/*
 * This file is part of SeAT
 *
 * Copyright (C) 2015, 2016, 2017, 2018, 2019  Leon Jacobs
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
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

    /**
     * FileCache constructor.
     * @throws CachePathException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidContainerDataException
     */
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

    /**
     * @return bool
     * @throws CachePathException
     */
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
