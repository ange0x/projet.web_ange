<?php

it('répond sur la route de santé de l\'application', function () {
    $this->get('/up')->assertOk();
});
