@component('mail::message')
Bonjour {{ $name }}

Trouvez ci dessous le lien de paiement dont vous avez genere.
<div style="max-width: 100%; overflow-wrap: break-word;" >
    <a href="{{ $link }}">
        {{ $link }}.
    </a>
</div>

@component('mail::button', ['url' => $link])
Ouvrir Checkout
@endcomponent

Merci,<br>
{{ config('app.name') }}
@endcomponent
