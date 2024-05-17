@component('mail::message')
Bienvenu sur notre plateforme, {{ $name }}

Vous avez fait une tentative de paiement rapid d'un de nos produits. Nous vous avons cree un compte mais il est incomplet.
S'il vous plait, Veillez completer vos informations pour acceder a votre compte et profiter de vos abonnement chez nous

@component('mail::button', ['url' => $link])
Completer mon profile
@endcomponent

Merci,<br>
{{ config('app.name') }}
@endcomponent
