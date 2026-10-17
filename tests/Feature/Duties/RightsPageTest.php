<?php

it('Rule 14(1): the rights page shows how to ask and the identifier needed, without sign-in', function () {
    $this->get('/data-rights')->assertOk()
        ->assertSee('How to make a request')
        ->assertSee('Please give the email address you sign in with, so we can find your data.')
        ->assertSee('You do not need an account to ask.')
        ->assertSee('We reply within 90 days of receiving your request.')
        ->assertSee('using the means the Board publishes')
        ->assertSee(route('data-rights.requests.index'));

    config()->set('data-rights.requests.identifier', ['en' => 'your customer number', 'hi' => '[hi] your customer number']);
    config()->set('data-rights.board.url', 'https://board.example');

    $this->get('/data-rights')->assertSee('Please give your customer number')
        ->assertSee('<a href="https://board.example" dir="ltr">https://board.example</a>', false);
    $this->get('/data-rights?lang=hi')->assertSee('[hi] your customer number');
});
