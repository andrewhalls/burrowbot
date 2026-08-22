<?php

declare(strict_types=1);

use App\Support\Giveaways\RenderWinnerMessage;

it('substitutes both placeholders', function () {
    $render = new RenderWinnerMessage;

    $result = $render('Congrats {winner}! You won {prize}.', '111', 'Golden Coin');

    expect($result)->toBe('Congrats <@111>! You won Golden Coin.');
});

it('substitutes only the placeholder present in the template', function () {
    $render = new RenderWinnerMessage;

    $result = $render('Congrats {winner}!', '111', 'Golden Coin');

    expect($result)->toBe('Congrats <@111>!');
});

it('leaves a template with neither placeholder unchanged', function () {
    $render = new RenderWinnerMessage;

    $result = $render('Someone just won something!', '111', 'Golden Coin');

    expect($result)->toBe('Someone just won something!');
});

it('leaves an unrecognized token literal', function () {
    $render = new RenderWinnerMessage;

    $result = $render('Hello {other} and {winner}', '111', 'Golden Coin');

    expect($result)->toBe('Hello {other} and <@111>');
});
