<?php

namespace Tests;

use App\Models\ApiUser;
use Illuminate\Foundation\Testing\TestResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

abstract class ApiTestCase extends TestCase
{
    /**
     * API prefix
     */
    protected const PREFIX = '/api/v1';

    /**
     * API doc
     *
     * @var array
     */
    protected $requests = [];

    /**
     * API token
     *
     * @var string|null
     */
    protected $token = null;

    /**
     * Insert record into request list
     *
     * @param array|null $data
     * @param \Illuminate\Foundation\Testing\TestResponse $response
     * @return void
     */
    protected function insertRequest($data, TestResponse $response)
    {
        if (is_null(env('GENERATE_API_DOC'))) {
            return;
        }
        $api = [
            'uri' => $this->processedUri(),
            'request' => $data,
            'status_code' => $response->status(),
            'header' => is_null($this->token) ? [] : [
                'Authorization' => $this->token,
            ],
            'description' => $response->status() == 200
                ? 'Successful operation'
                : $response->json('message'),
            'response' => empty($response->content()) ? null : $response->json(),
        ];
        array_push($this->requests, $api);
    }

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        if (!is_null(env('GENERATE_API_DOC'))) {
            $this->beforeApplicationDestroyed(function () {
                DB::connection('sqlite_api_doc')
                    ->table('apis')
                    ->insert([
                        'value' => json_encode([
                            'method' => $this->method(),
                            'uri' => $this::PREFIX . $this->uri(),
                            'summary' => $this->summary(),
                            'tag' => $this->tag(),
                            'authorization' => !is_null($this->token),
                            'params' => $this->params(),
                            'requests' => $this->requests
                        ]),
                    ]);
            });
        }
    }

    /**
     * Assert JSON API succeed
     *
     * @param array|null $data
     * @param bool $documented [optional]
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    protected function assertSucceed($data, bool $documented = true)
    {
        $response = $this->request($data);
        $response->assertStatus(200);
        if ($documented) {
            $this->insertRequest($data, $response);
        }
        return $response;
    }

    /**
     * Assert JSON API Failed
     *
     * @param array|null $data
     * @param int $code
     * @param bool $documented [optional]
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    protected function assertFailed($data, int $code, bool $documented = true)
    {
        $response = $this->request($data);
        $response->assertStatus($code);
        if ($documented) {
            $this->insertRequest($data, $response);
        }
        return $response;
    }

    /**
     * Make request
     *
     * @param array|null $data
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    protected function request($data)
    {
        $request = $this;
        if (!is_null($this->token)) {
            $request->withHeader('Authorization', $this->token);
        }
        return is_null($data)
            ? $request->call($this->method(), $this->processedUri())
            : $request->json($this->method(), $this->processedUri(), $data);
    }

    /**
     * Get the processed uri
     *
     * @return string
     */
    protected function processedUri()
    {
        $uri = $this->uri();
        foreach ($this->uriParams() as $key => $value) {
            $uri = str_replace('{' . $key . '}', $value, $uri);
        }
        return $this::PREFIX . $uri;
    }

    /**
     * Login for authorization
     *
     * @param \App\Models\ApiUser $user
     * @return void
     */
    protected function login(ApiUser $user)
    {
        Auth::guard('api')->login($user);
        $this->token = Auth::guard('api')->token();
    }

    /**
     * Get the API method
     *
     * @return string
     */
    protected function method()
    {
        return 'GET';
    }

    /**
     * Get the API uri
     *
     * @return string
     */
    protected function uri()
    {
        throw new \BadMethodCallException('uri() not implemented');
    }

    /**
     * Get the uri params
     *
     * @return array
     */
    protected function uriParams()
    {
        return [];
    }

    /**
     * Get the API summary
     *
     * @return string
     */
    protected function summary()
    {
        throw new \BadMethodCallException('summary() not implemented');
    }

    /**
     * Get the tag
     *
     * @return string
     */
    protected function tag()
    {
        throw new \BadMethodCallException('tag() not implemented');
    }

    /**
     * Get validation rules
     *
     * @return array
     */
    protected function rules()
    {
        throw new \BadMethodCallException('rules() not implemented');
    }

    /**
     * Get params
     *
     * @return array
     */
    protected function params()
    {
        $params = [];
        foreach ($this->rules() as $key => $rule) {
            $restrictions = explode('|', $rule);
            $param = [
                'key' => $key,
            ];
            $extra = [];
            foreach ($restrictions as $restriction) {
                switch ($restriction) {
                    case 'required':
                        $param['required'] = true;
                        break;
                    case 'string':
                    case 'boolean':
                    case 'numeric':
                    case 'integer':
                    case 'phone:US':
                    case 'zip_code':
                        $param['type'] = $restriction;
                        break;
                    case 'email':
                        $param['email'] = true;
                        break;
                    default:
                        array_push($extra, $restriction);
                }
            }
            $param['extra'] = implode(', ', $extra);
            array_push($params, $param);
        }
        return $params;
    }
}
