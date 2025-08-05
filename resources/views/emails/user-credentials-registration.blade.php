# Bienvenue {{ $user->name }} !

Votre compte a été créé avec succès.

**Identifiants de connexion :**
- Email : {{ $user->email }}
- Mot de passe : {{ $password }}

[Se connecter]({{ config('app.url') }}/login)

---
Équipe {{ config('app.name') }}