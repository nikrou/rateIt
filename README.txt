rateIt 0.5 (2009/07/09) plugin for Dotclear 2

Préambule:
==========

Cette extension pour Dotclear 2 permet d'ajouter un système complet 
de notation pour les billets. (et plus avec ses addons).

Cette documentation est en cours d'écriture...


I. Licence:
===========

This file is part of rateIt, a plugin for Dotclear 2.
Copyright (c) 2009 JC Denis and contributors
jcdenis@gdwd.com
Licensed under the GPL version 2.0 license.
A copy of this license is available at
http://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Some icons from Silk icon set 1.3 by Mark James at:
http://www.famfamfam.com/lab/icons/silk/
under a Creative Commons Attribution 2.5 License at
http://creativecommons.org/licenses/by/2.5/

The javascript Cookie plugin
Copyright (c) 2006 Klaus Hartl (stilbuero.de)
is licensed under the MIT License and the GPL License.
http://www.opensource.org/licenses/mit-license.php
http://www.gnu.org/licenses/gpl.html

The javascript Star Rating Plugin by Fyneworks.com 
Copyright (c) 2008 Fyneworks.com
is licensed under the MIT License and the GPL License.
http://www.opensource.org/licenses/mit-license.php
http://www.gnu.org/licenses/gpl.html


II. Support:
============

http://dotclear.jcdenis.com/
http://forum.dotclear.net/viewtopic.php?id=39801
http://lab.dotclear.org/wiki/plugin/rateIt


III. Installation:
==================

Voir la procédure d'installation des plugins Dotclear 2.
Pour information, le plugin rateIt créé la table "rateit".


IV. Désintallation:
===================

Une procédure est disponible à partir du gestionnaire d'extension 
ou depuis l'onglet "désinstallation" de l'extension "RateIt".
Si la désintallation est impossible par cette procédure, 
il faut supprimer les fichiers et la table rateit manuellement.


V. Onglet "Administration":
===========================

V.1 Extension:
--------------

"Activer l'extension"
Permet d'activer ou non l'extension sur un blog. 
Avec une configuration d'origine, la désactivation de l'extension 
masquera toutes les balise en rapport avec le plugin coté publique.

"Identifier l'utilisateur par"
Methode de gestion des votes, soit par Ip, soit par cookie, soit les deux.
Chaque méthode a ses avantages et ses inconvénients.

V.2 Note:
---------

"Note sur"
Permet de modifier le quotient de la note de 2 à 20. (exemple de note 5/20)

"Nombre de décimals"
Permet d'arrondir le résultat affiché à x chiffres après la virgule. (de 0 à 4)

"Message de remerciement"
Après un vote l'interface peut être modifié en affichant un message au votant.
Si le message est vide, l'interface ne sera pas modifié.

V.2 Image:
----------

Vous pouvez modifer l'apparence de l'interface de vote avec 
un choix prédéfinie d'image ou en en ajoutant une nouvelle.
La nouvelle image doit obligatoirement être à format "png" 
et avec trois parties égales en hauteur:
- Une partie haute représentant "un non vote", 
- une partie centrale représentant un vote positif et 
- une partie basse représentant le survole par la souris.
La largeur et la hauteur sont à votre convenance.

L'ordre de recherche de l'image est:
1) dans le theme utilisé par le blog, fichier: /img/rateit-stars.png
2) dans le repertoire public du blog, fichier: /rateit/rateit-stars.png
3) dans le repertoire de l'extention, fichier: /default-template/img/rateit-stars.png


VI. Onglet "Désintallation":
============================

...


VII. Onglet "A propos":
=======================

Donne des indications sur l'extension.
La version, Les droits, des liens vers le support...


VIII. Onlget "Billets":
======================


VIII.1 Options:
---------------

"Inclure dans les pages des billets":
Utilisé pour le template post.html.
Permet d'inclure l'outil de vote directement à la fin d'un billet sans modifier les templates.
La balise {{tpl:SysBehavior behavior="publicEntryAfterContent"}} doit être présente 
dans le thème utilisé pour que cete option fonctionne.

"Inclure sur la page d'accueil":
Utilisé pour le template home.html.Idem que ci-dessus.

"Inclure sur la page des catégories":
Utilisé pour le template category.html. Idem que ci-dessus.

"Limiter à une catégorie":
Permet de limiter les votes à une seule catégorie.


IX. Autres onlgets:
===================

D'autres onglets peuvent être présents si d'autres plugins 
utilisent l'interface "rateIt".
...


X. Widgets:
===========

2 widgets sont disponibles:

X.1 Evaluation:
---------------

Ce widget (de class "rateitwidget" ) permet d'afficher une interface de vote sur la page d'un billet.

"Autoriser le vote pour les billets"
Si cette option est cochée et qu'on est sur la page d'un billet, 
le widget affichera un formulaire de vote.

"Titre pour les billets"
Permet de modifier le titre du widget pour le vote sur les billets.
Si ce champs est vide alors la balise de titre ne sera pas présente.

"..."
D'autres options sont possibles ici si d'autres plugins utilisent la même interface.
Par exemple le vote pour des catégories, des tags...
Un behavior "parseWidgetRateItVote" est disponible ici.

"Afficher la note complète"
Ajoute l'affichage d'une balise de class "rateit-fullnote" contenant:
- soit la note complète, exemple: 5/20,
- soit la note en pourcentage, exemple: 25%
- soit la balise n'est pas présente si "cacher" est selectionné.

"Afficher la note"
Affiche la note dans une list-item,

"Afficher le nombre de votes"
Idem ci-dessus.

"Afficher la note la plus haute"
Idem ci-dessus.

"Affiher la note la plus basse"
Idem ci-dessus.


X.2 Top évaluation:
-------------------

Ce widget (de class "rateitpostsrank") permet d'afficher un classement des votes.

"Titre"
Permet de modifier le titre du widget.
Si ce champ est vide la balise de titre ne sera pas présente.

"Type"
Par défaut seul le type "Billets" est présent.
D'autres type sont possibles ici si d'autres plugins utilisent la même interface.
Un behavior "parseWidgetRateItRank" est disponible ici.

"Longueur"
Nombre de billets à afficher.

"Trier par"
Il est possible de trier les résultats par nombre de vote ou par note.

"Trier"
Permet de modifier l'ordre. Croissant ou décroissant.

"Texte"
Permet de mettre en forme le résultat avec comme options:
- %rank% : le rang (1, 2, 3...)
- %title% : le titre du billet,
- %note% : la note
- %quotient% : le quotient,
- %percent% : la note en pourcentage,
- %count% : le nombre de vote.

"Uniquement sur la page d'accueil"
Affiche le widget uniquement sur la page d'accueil du blog.


XI. Comment modifier l'apparence de l'extension?
================================================

XI.1 Emplacement des fichiers:
------------------------------

...

XI.2 les widgets:
-----------------

a) Widget "Evaluation":

Voici la structure type de ce widget:

<div class="rateitwidget">
 <h2>titre</h2>
 <p><span id="xxx" class="rateit-fullnote">0/10</span></p>
 <form class="rateit-linker" id="xxx" action="xxx" method="post">
  <p>
   <input name="xxx" class="rateit-type-id" type="radio" value="1"/>
   <input name="xxx" class="rateit-type-id" type="radio" value="2"/>
   ...
   <input type="submit" name="submit" value="Voter"/>
  </p>
 </form>
 <ul>
  <li>Note:<span id="xxx" class="rateit-note">0</span></li>
  <li>Vote:<span id="xxx" class="rateit-vote">0</span></li>
  <li>Plus haute:<span id="xxx" class="rateit-higher">0</span></li>
  <li>Plus basse:<span id="xxx" class="rateit-lower">0</span></li>
 </ul>
</div>

La structure de la balise "form" est modifiée par le javascript de notation.
La structure CSS en rapport avec ce javascript est directement généré dans 
le code source de la page.

b) Widget "Top évaluation":

Voici la structure type de ce widget:

<div class="rateitpostsrank">
 <h2>titre</h2>
 <ul>
  <li>texte</li>
  <li>texte</li> ou
  <li><span class="rateit-rank">1</span>texte</li>
  ...
 </ul>
</div>

XI.3 Formulaires inclus dans la page:
-------------------------------------

Son emplacement dépend du thème utilisé sur le blog.
Par défaut il se situe après le contenu d'un billet 
et utilise le behavior {{tpl:SysBehavior behavior="publicEntryAfterContent"}}
Son apparence dépend également du thème.
Par défaut il utilise le fichier "default-templates/tpl/rateit.html" de l'extension.


XII. Comment étendre cette extension à d'autres types de notation?
==================================================================

...


XIII. Behaviors:
================

XIII.1 callBehavior:
---------------------

"addRateItType":

"rateitGetRates":

"adminRateItTabs":

"templateRateItRedirect":

"publicRatingBlocsRateit":

"templateRateIt":

"templateRateItTitle":

"initWidgetRateItVote":

"parseWidgetRateItVote":

"initWidgetRateItRank":

"parseWidgetRateItRank":


XIII.2 addBehavior:
--------------------

"pluginsBeforeDelete":

"adminBeforePostDelete":

"adminPostsActionsCombo":

"adminPostsActions":

"adminPostsActionsContent":

"exportFull":

"exportSingle":

"importInit":

"importSingle":

"importFull":

"publicHeadContent":

"publicEntryAfterContent":

"initWidgets":


XIV. Public Urls, values, blocks:
=================================

XIV.1 Urls:
-----------

"rateit":

"rateitnow":

"rateitservice":

XIV.2 blocks:
-------------

"rateIt":

"rateItIf":

XIV.3 values:
-------------

"rateItLinker":

"rateItTitle":

"rateItTotal":

"rateItMax":

"rateItMin":

"rateItNote":

"rateItFullnote":

"rateItQuotient":


XV. Javascripts:
================

...


XVI. Base de données:
=====================

XVI.1 Structure:
----------------

CREATE TABLE `dc_rateit` (
  `blog_id` varchar(32) collate utf8_bin NOT NULL,
  `rateit_id` varchar(255) collate utf8_bin NOT NULL,
  `rateit_type` varchar(64) collate utf8_bin NOT NULL,
  `rateit_note` int(11) NOT NULL,
  `rateit_quotient` int(11) NOT NULL,
  `rateit_ip` varchar(64) collate utf8_bin NOT NULL,
  `rateit_time` datetime NOT NULL default '1970-01-01 00:00:00',
  PRIMARY KEY  (`blog_id`,`rateit_type`,`rateit_id`,`rateit_ip`),
  KEY `dc_idx_rateit_blog_id` USING BTREE (`blog_id`),
  KEY `dc_idx_rateit_rateit_type` USING BTREE (`rateit_type`),
  KEY `dc_idx_rateit_rateit_id` USING BTREE (`rateit_id`),
  KEY `dc_idx_rateit_rateit_ip` USING BTREE (`rateit_ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


XVII. Arborescence:
===================

/rateIt

/rateIt/default-templates

/rateIt/default-templates/img

/rateIt/default-templates/img/stars

/rateIt/default-templates/js

/rateIt/default-templates/tpl

/rateIt/inc

/rateIt/locales

/rateIt/locales/fr


XVIII. Remerciements:
=====================

Je tiens à remiercier les personnes qui ont eu la patience de tester toutes les versions d'essais
et de donner un coup de main. Je remercie également toute l'équipe de Dotclear.

-----------
End of file