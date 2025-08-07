<x-mail::message>
# Bienvenue {{ $user->name }} !

Votre compte a été créé avec succès.

<x-mail::panel>
**Identifiants de connexion :**
- **Email :** {{ $user->email }}
- **Mot de passe :** {{ $password }}
</x-mail::panel>

<x-mail::button :url="config('app.url') . '/login'">
Se connecter
</x-mail::button>

Merci,<br>
L'équipe {{ config('app.name') }}
</x-mail::message>
