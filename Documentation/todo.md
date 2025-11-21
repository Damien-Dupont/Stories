4. 🔴 Problème majeur : chapter_title redondant

ce n'est pas un problème. je me suis trompé et toi aussi. La requête SQL est un LEFT JOIN, la table scène ne stocke bien que le chapter_id et on récupère le titre via le join, rien de plus normal. Créer une route dédiée est utile pour une table des matières. J'aurai besoin de ça plus tard dans le front, et il te faudra m'expliquer comment créer cette route, pour l'instatn je n'ai pas compris.

Les tests de création de scènes normales et spéciales sont bons.

il faut tester leur lecture, leur updates et leur delete. Ce qui m'amène au TDD, qui était au coeur du projet et que j'ai oublié en chemin.

Pour chaque route, chaque fonctionnalité, il faut un test dédié. QUand on aura créé les tests de tout ce qu'on a déjà fait, il faudra créer les tests de ce qui reste à faire avant/en même temps que le code lui-même.

Donc ce soir, on teste les routes, et si on a le temps on avance dans le sceneController
