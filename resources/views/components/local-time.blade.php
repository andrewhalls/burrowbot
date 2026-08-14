@props(['at'])

<time data-utc-datetime="{{ $at->toIso8601String() }}">{{ $at->format('M j, Y g:ia') }} UTC</time>
