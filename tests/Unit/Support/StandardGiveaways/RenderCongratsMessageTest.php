<?php

declare(strict_types=1);

use App\Support\StandardGiveaways\RenderCongratsMessage;
use Illuminate\Support\Carbon;

it('substitutes all four placeholders', function () {
    $render = new RenderCongratsMessage;
    $deadline = Carbon::create(2026, 8, 20, 12, 0, 0, 'UTC');

    $result = $render(
        'Congrats {winners}! You won {prize}. Claim at {claim_link} by {claim_deadline}.',
        ['111', '222'],
        'Fairy Mantis',
        'https://discord.com/channels/1/2',
        $deadline,
    );

    expect($result)->toBe(
        "Congrats <@111>, <@222>! You won Fairy Mantis. Claim at https://discord.com/channels/1/2 by <t:{$deadline->getTimestamp()}:R>."
    );
});

it('substitutes only the placeholders present in the template', function () {
    $render = new RenderCongratsMessage;

    $result = $render('Congrats {winners}!', ['111'], 'Prize', 'link', Carbon::now());

    expect($result)->toBe('Congrats <@111>!');
});

it('leaves a template with no placeholders unchanged', function () {
    $render = new RenderCongratsMessage;

    $result = $render('Thanks for playing!', ['111'], 'Prize', 'link', Carbon::now());

    expect($result)->toBe('Thanks for playing!');
});

it('leaves an unrecognized token literal', function () {
    $render = new RenderCongratsMessage;

    $result = $render('Hello {other} and {winners}', ['111'], 'Prize', 'link', Carbon::now());

    expect($result)->toBe('Hello {other} and <@111>');
});

it('renders an empty winners mention list when there are no winners', function () {
    $render = new RenderCongratsMessage;

    $result = $render('Winners: {winners}', [], 'Prize', 'link', Carbon::now());

    expect($result)->toBe('Winners: ');
});

it('renders an empty string for a null claim link or claim deadline', function () {
    $render = new RenderCongratsMessage;

    $result = $render('Link: {claim_link} Deadline: {claim_deadline}', ['111'], 'Prize', null, null);

    expect($result)->toBe('Link:  Deadline: ');
});
