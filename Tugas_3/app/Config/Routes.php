<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Dashboard Overview
$routes->get('/', 'Dashboard::index');

// Manajemen Pengguna (Users)
$routes->group('users', function ($routes) {
    $routes->get('/', 'Users::index');
    $routes->post('store', 'Users::store');
    $routes->post('update/(:num)', 'Users::update/$1');
    $routes->get('delete/(:num)', 'Users::delete/$1');
    $routes->post('delete/(:num)', 'Users::delete/$1');
});

// Manajemen Produk (Products)
$routes->group('products', function ($routes) {
    $routes->get('/', 'Products::index');
    $routes->post('store', 'Products::store');
    $routes->post('update/(:num)', 'Products::update/$1');
    $routes->get('delete/(:num)', 'Products::delete/$1');
    $routes->post('delete/(:num)', 'Products::delete/$1');
});

// Simulasi Pembelian & Transaksi (Transactions)
$routes->group('transactions', function ($routes) {
    $routes->get('/', 'Transactions::index');
    $routes->post('store', 'Transactions::store');
    $routes->post('update/(:num)', 'Transactions::update/$1');
    $routes->get('delete/(:num)', 'Transactions::delete/$1');
    $routes->post('delete/(:num)', 'Transactions::delete/$1');
});
