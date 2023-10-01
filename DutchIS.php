<?php

namespace App\Extensions\Servers\DutchIS;

use App\Classes\Extensions\Server;
use App\Helpers\ExtensionHelper;
use App\Models\OrderProduct;
use App\Models\Product;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class DutchIS extends Server
{
    private $httpClient;

    public function __construct()
    {
        $this->httpClient = new Client([
            'base_uri' => "https://dutchis.net",
            'headers'  => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . ExtensionHelper::getConfig('DutchIS', 'apiToken'),
                'X-Team-Uuid' => ExtensionHelper::getConfig('DutchIS', 'teamUuid'),
            ],
        ]);    
    }

    public function getConfig()
    {
        return [
            [
                'name' => 'apiToken',
                'friendlyName' => 'API Token',
                'type' => 'text',
                'required' => true,
                'description' => 'The API Token of your DutchIS account.',
                'hostname' => 'required|string',
            ], [
                'name' => 'teamUuid',
                'friendlyName' => 'Team UUID',
                'type' => 'text',
                'required' => true,
                'description' => 'The UUID of the team you want to use for paymenter.',
                'hostname' => 'required|string',
            ]
        ];
    }

    public function getProductConfig($options)
    {
        return [
            [
                'type' => 'title',
                'friendlyName' => 'General',
                'description' => 'General options',
            ], [
                'name' => 'class',
                'type' => 'dropdown',
                'friendlyName' => 'Hardware Class',
                'required' => true,
                'description' => 'The type of hardware you want to deploy to.',
                'options' => [
                    [
                        'name' => 'Standard (AMD EPYC MILAN)',
                        'value' => 'standard'
                    ],
                    [
                        'name' => 'Performance (AMD Ryzen 9 5900x)',
                        'value' => 'performance'
                    ]
                ]
            ], [
                'name' => 'billing',
                'type' => 'dropdown',
                'friendlyName' => 'DutchIS billing cycle',
                'required' => true,
                'description' => 'The billing cycle you want to use on DutchIS. This is not your products billing cycle.',
                'options' => [
                    [
                        'name' => 'Hourly billing (Pay afterwards)',
                        'value' => 'hourly'
                    ],
                    [
                        'name' => 'Monthly billing (Pay upfront)',
                        'value' => 'monthly'
                    ]
                ]
            ],

            [
                'type' => 'title',
                'friendlyName' => 'Specifications',
                'description' => 'Virtual server hardware specifications',
            ], [
                'name' => 'vcores',
                'type' => 'text',
                'friendlyName' => 'vCores',
                'required' => true,
                'description' => 'The number of vcores of the virtual server',
                'validation' => 'required|integer|min:1|max:16',
            ], [
                'name' => 'memory',
                'type' => 'text',
                'friendlyName' => 'Memory (GB)',
                'required' => true,
                'description' => 'The amount of memory of the virtual server',
                'validation' => 'required|integer|min:1|max:64',
            ], [
                'name' => 'storage',
                'type' => 'text',
                'friendlyName' => 'Storage (GB)',
                'required' => true,
                'description' => 'The amount of storage of the virtual server',
                'validation' => 'required|integer|min:20|max:1000',
            ], [
                'name' => 'network',
                'type' => 'text',
                'friendlyName' => 'Network Speed (Gbps)',
                'description' => 'The network speed of the virtual server',
                'validation' => 'required|integer|min:1|max:10',
            ],
        ];
    }

    public function dutchisRequest(string $method, string $url, array $options = []) {
        return json_decode($this->httpClient->request($method, $url, $options)->getBody()->getContents());
    }

    public function getUserConfig(Product $product)
    {
        return [
            [
                'name' => 'hostname',
                'type' => 'text',
                'friendlyName' => 'Hostname',
                'description' => 'The hostname of your virtual server',
                'validation' => 'required|max:50|alpha',
                'required' => true,
            ], [
                'name' => 'username',
                'type' => 'text',
                'friendlyName' => 'Username',
                'description' => 'Your username which is used to login with.',
                'validation' => 'required|string|min:4|max:50|alpha|not_in:root',
                'required' => true,
            ], [
                'name' => 'password',
                'type' => 'password',
                'friendlyName' => 'Password',
                'description' => 'The password used for console/vnc access and remote desktop on windows servers.',
                'validation' => 'required|string|min:8|max:50',
                'required' => true,
            ], [
                'name' => 'sshkey',
                'type' => 'text',
                'friendlyName' => 'SSH Key',
                'description' => 'Used for SSH access on linux virtual servers.',
                'validation' => 'required|string',
                'required' => true,
            ], [
                'name' => 'os',
                'type' => 'dropdown',
                'friendlyName' => 'Operating System',
                'required' => true,
                'description' => 'The operating system you would like to install on your virtual server.',
                'options' => [
                    [
                        'name' => 'Ubuntu 22.04',
                        'value' => 'ubuntu-22-04'
                    ], [
                        'name' => 'Ubuntu 20.04',
                        'value' => 'ubuntu-20-04'
                    ], [
                        'name' => 'Debian 11',
                        'value' => 'debian-10'
                    ], [
                        'name' => 'Debian 10',
                        'value' => 'debian-11'
                    ], [
                        'name' => 'Fedora 38',
                        'value' => 'fedora-38'
                    ], [
                        'name' => 'OpenSUSE Leap 15.5',
                        'value' => 'opensuse-leap-15-5'
                    ], [
                        'name' => 'Gentoo 2023',
                        'value' => 'gentoo-2023'
                    ], [
                        'name' => 'CentOS 9',
                        'value' => 'centos-9'
                    ], [
                        'name' => 'Windows Server 2022',
                        'value' => 'windows-2022'
                    ]
                ]
            ],
        ];
    }

    public function createServer($user, $params, $order, $product, $configurableOptions)
    {
        $storage = isset($configurableOptions['storage']) ? $configurableOptions['storage'] : $params['storage'];
        $class = isset($configurableOptions['class']) ? $configurableOptions['class'] : $params['class'];
        $vCores = isset($configurableOptions['vcores']) ? $configurableOptions['vcores'] : $params['vcores'];
        $memory = isset($configurableOptions['memory']) ? $configurableOptions['memory'] : $params['memory'];
        $network = isset($configurableOptions['network']) ? $configurableOptions['network'] : $params['network'];
        $billing = isset($configurableOptions['billing']) ? $configurableOptions['billing'] : $params['billing'];

        $response = $this->dutchisRequest("POST", "/api/v1/virtualservers", [
            'json' => [
                "os" => $params['config']['os'],
                "sshkeys" => [$params['config']['sshkey']],
                "hostname" => $params['config']['hostname'],
                "username" => $params['config']['username'],
                "password" => $params['config']['password'],
                "billing_type" => $billing,

                "disk" => $storage,
                "class" => $class,
                "cores" => $vCores,
                "memory" => $memory,
                "network" => $network,
            ]
        ]);

        if (!$response->uuid) throw new Exception('Unable to create server');

        ExtensionHelper::setOrderProductConfig('vsUuid', $response->uuid, $product->id);

        return true;
    }

    public function suspendServer($user, $params, $order, $product, $configurableOptions)
    {
        throw new Exception('Not implemented');
    }

    public function unsuspendServer($user, $params, $order, $product, $configurableOptions)
    {
        throw new Exception('Not implemented');
    }

    public function terminateServer($user, $params, $order, $product, $configurableOptions)
    {
        $vsUuid = $params['config']['vsUuid'];

        try {
            $this->dutchisRequest("DELETE", "/api/v1/virtualservers/$vsUuid");
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }


    public function getCustomPages($user, $params, $order, $product, $configurableOptions)
    {
        $vsUuid = $params['config']['vsUuid'];

        $virtualServer = $this->dutchisRequest('GET', "/api/v1/virtualservers/$vsUuid")->data;

        return [
            'name' => 'DutchIS',
            'template' => 'dutchis::control',
            'data' => [
                'virtualserver' => $virtualServer,
                'httpClient' => $this->httpClient,
            ],
            'pages' => [
                [
                    'template' => 'dutchis::stats',
                    'name' => 'Statistics',
                    'url' => 'stats',
                ],
                [
                    'template' => 'dutchis::vnc',
                    'name' => 'Console',
                    'url' => 'console',
                ],
            ]
        ];
    }

    public function power(Request $request, OrderProduct $product)
    {
        if (!ExtensionHelper::hasAccess($product,  $request->user())) throw new Exception('You do not have access to this server');
        
        $request->validate([
            'status' => ['required', 'string', 'in:stop,start,reboot,shutdown'],
        ]);

        $data = ExtensionHelper::getParameters($product);
        $params = $data->config;
        $vsUuid = $params['config']['vsUuid'];

        $resp = $this->dutchisRequest('POST', "/api/v1/virtualservers/$vsUuid/power", [
            'json' => [
                'powerstate' => $request->status,
            ],
        ]);
        if (!$resp->data) throw new Exception('Unable to ' . $request->status . ' server');

        return response()->json([
            'status' => 'success',
            'message' => 'Server has been ' . $request->status . 'ed successfully'
        ]);
    }
}
