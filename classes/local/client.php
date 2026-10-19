<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_innealla\local;

/**
 * HTTP client for the Innealla moodle-sync API.
 *
 * @package   local_innealla
 * @copyright 2026 Inneall <support@inneall.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class client {
    /** @var string */
    private $baseurl;

    /** @var string */
    private $institutionid;

    /** @var string */
    private $apikey;

    /**
     * Create a client from explicit values or plugin config.
     *
     * @param string|null $baseurl SIS API base ending in /api
     * @param string|null $institutionid Institution cuid
     * @param string|null $apikey Shared secret
     */
    public function __construct(
        ?string $baseurl = null,
        ?string $institutionid = null,
        ?string $apikey = null
    ) {
        $this->baseurl = rtrim($baseurl ?? (string) get_config('local_innealla', 'sisbaseurl'), '/');
        $this->institutionid = $institutionid ?? (string) get_config('local_innealla', 'institutionid');
        $this->apikey = $apikey ?? (string) get_config('local_innealla', 'apikey');
    }

    /**
     * Whether connection settings are present.
     *
     * @return bool
     */
    public function configured(): bool {
        return $this->baseurl !== '' && $this->institutionid !== '' && $this->apikey !== '';
    }

    /**
     * GET JSON from a moodle-sync path.
     *
     * @param string $path Relative path under the API base
     * @param array $query Query string parameters
     * @return object Decoded JSON object
     */
    public function get(string $path, array $query = []): object {
        return $this->request('GET', $path, null, $query);
    }

    /**
     * POST JSON to a moodle-sync path.
     *
     * @param string $path Relative path under the API base
     * @param array $body JSON body
     * @return object Decoded JSON object
     */
    public function post(string $path, array $body = []): object {
        return $this->request('POST', $path, $body);
    }

    /**
     * Perform an authenticated HTTP request.
     *
     * @param string $method GET or POST
     * @param string $path Relative path
     * @param array|null $body JSON body for POST
     * @param array $query Query parameters
     * @return object
     */
    private function request(
        string $method,
        string $path,
        ?array $body = null,
        array $query = []
    ): object {
        if (!$this->configured()) {
            throw new \moodle_exception('error_notconfigured', 'local_innealla');
        }

        $url = $this->baseurl . '/' . ltrim($path, '/');
        if ($query) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($query);
        }

        $headers = [
            'X-Innealla-Key: ' . $this->apikey,
            'X-Innealla-Institution: ' . $this->institutionid,
            'Accept: application/json',
            'Content-Type: application/json',
        ];

        $curl = new \curl();
        $curl->setHeader($headers);
        if ($method === 'GET') {
            $raw = $curl->get($url);
        } else {
            $raw = $curl->post($url, json_encode($body ?? []));
        }

        if (!empty($curl->get_errno())) {
            throw new \moodle_exception('error_http', 'local_innealla', '', (object) [
                'method' => $method,
                'path' => $path,
                'code' => 0,
                'snippet' => 'curl errno ' . $curl->get_errno() . ': ' . $curl->error,
            ]);
        }

        $info = $curl->get_info();
        $code = (int) ($info['http_code'] ?? 0);
        $data = json_decode((string) $raw);
        if ($code < 200 || $code >= 300 || !is_object($data)) {
            throw new \moodle_exception('error_http', 'local_innealla', '', (object) [
                'method' => $method,
                'path' => $path,
                'code' => $code,
                'snippet' => substr((string) $raw, 0, 500),
            ]);
        }

        return $data;
    }

    /**
     * Health probe.
     *
     * @return object
     */
    public function health(): object {
        return $this->get('moodle-sync/health');
    }

    /**
     * Hub feature manifest.
     *
     * @return object
     */
    public function manifest(): object {
        return $this->get('moodle-sync/manifest');
    }
}
