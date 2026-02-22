<?php

use FastRoute\RouteCollector;
use App\Http\Controllers\Admin\IdentityController;

return function (RouteCollector $r) {
    // Identity Routes
    $r->addGroup('/api', function (RouteCollector $r) {
        $r->post('/admin/students/import/preview', function() {
            (new IdentityController())->preview();
        });
        $r->post('/admin/students/import/commit', function() {
            (new IdentityController())->commit();
        });
        $r->get('/admin/students/credentials/export', function() {
            (new IdentityController())->export();
        });
    });
};
