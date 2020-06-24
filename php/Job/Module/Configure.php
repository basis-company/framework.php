<?php

namespace Basis\Job\Module;

use Basis\Toolkit;
use Basis\Registry;
use Symfony\Component\Yaml\Yaml;

class Configure
{
    use Toolkit;

    public function run(Registry $registry)
    {
        $configuration = $this->getDefaultConfiguration();

        $collect = [];
        foreach ($registry->listClasses('metric') as $class) {
            $metric = $this->get($class);
            $collect[$metric->getNick()] = $metric->toArray();
        }
        if (count($collect) > 0) {
            $configuration['metrics']['collect'] = $collect;
        }

        $yaml = Yaml::dump($configuration, 10, 2);
        file_put_contents('.rr.yaml', $yaml);
    }

    private function getDefaultConfiguration(): array
    {
        $configuration = [
            'health' => [
                'address' => '0.0.0.0:8000',
            ],
            'http' => [
                'address' => '0.0.0.0:80',
                'workers' => [
                    'command' => 'php server',
                    'pool' => [
                        'allocateTimeout' => 60,
                        'destroyTimeout' => 60,
                        'maxJobs' => 128,
                        'numWorkers' => 4,
                    ]
                ],
            ],
            'limit' => [
                'interval' => 1,
                'services' => [
                    'http' => [
                        'maxMemory' => 256,
                    ],
                ],
            ],
            'metrics' => [
                'address' => '0.0.0.0:8080',
            ],
            'rpc' => [
                'enable' => true,
                'address' => 'tcp://0.0.0.0:6001',
            ],
            'static' => [
                'dir' => '.',
            ],
        ];

        if (getenv('BASIS_ENVIRONMENT') === 'dev') {
            $configuration['reload'] = [
                'interval' => '1s',
                'patterns' => [
                    '.js',
                    '.php',
                    '.styl',
                ],
                'services' => [
                    'http' => [
                        'dirs' => [
                            '.',
                        ],
                        'recursive' => true,
                    ],
                ],
            ];
        }

        return $configuration;
    }
}
