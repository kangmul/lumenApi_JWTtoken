<?php

$router->group([], function () use ($router) {
  $router->get('/test', 'AuthController@testcontroller');
  $router->post('/login', 'AuthController@login');
});
