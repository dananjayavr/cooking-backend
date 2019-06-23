# Backend du projet cooking.com

Le back-end sera accessible à travers d'un API : https://cooking.com/api/

Général
-------

POST : /api/register - Créer un compte 

Paramètres requises : {email, password, password_confirmation, firstname, lastname}

POST : /api/login - se connecter

Paramètres requises : {email, password}

GET : /api/logout - se déconnecter

Recipe 
------

GET : /api/recipes - récupérer des recettes

POST : /api/recipes - création/ajout d'une recette

GET : /api/recipes/{id} - retrouver une recette avec une ID

DELETE : /api/recipes/{id} - supprimer une recette avec une ID

PUT : /api/recipes/{id} - mettre à jour la recette

User 
---

GET : /api/users - récupérer l'ensemble des utilisateurs

POST : /api/users- création/ajout d'un utilisateur

GET : /api/users/{id} - récupérer les informations d'un utilisateur

DELETE : /api/users/{id} - supprimer un utilisateur

PUT : /api/users/{id} - mettre à jour un utilisateur

Category 
---

GET : /api/categories - récupérer l'ensemble des catégories

POST : /api/categories - création/ajout d'une catégorie

GET : /api/categories/{id} - récupérer les informations d'une catégorie

DELETE : /api/categories/{id} - supprimer une catégorie

PUT : /api/categories/{id} - mettre à jour une catégorie