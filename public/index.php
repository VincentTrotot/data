<?php

use App\Kernel;

// déploiement en cours (bin/deployer) : la base de prod est en cours de migration
if (is_file(dirname(__DIR__).'/var/maintenance')) {
    http_response_code(503);
    header('Retry-After: 30');
    readfile(__DIR__.'/maintenance.html');
    exit;
}

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return static function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
