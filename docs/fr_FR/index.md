# Plugin Linksys

Plugin permettant de contrôler certains aspects de vos routeurs Linksys compatibles.

Ce qui est disponible :
- Modèle et firmware
- Status du Réseaux Invités et du Contrôle Parental
- Nombre d'équipements connectés au routeur par type de connexion
- Activer/Désactiver le contrôle parental
- Activer/Désactiver le réseau invités
- Reboot

>**Important**      
>**Important**      
>Le plugin a été testé avec un routeur Linksys Velop VLP01 et firmware 1.1.13.202617. Devrait fonctionner pour d'autres modèles également.

# Configuration

## Configuration du plugin

Le plugin **Linksys** ne nécessite aucune configuration spécifique et doit seulement être activé après l'installation.

Les données sont vérifiées toutes les 5 minutes.

## Configuration des équipements

Pour accéder aux différents équipements **Linksys**, dirigez-vous vers le menu **Plugins → Communication → Linksys**.

Sur la page de l'équiement; renseignez l'adresse IP locale du routeur, l'identifiant du compte Admin (normalement 'admin'), et le mot de passe du compte Admin.

# Contributions

Ce plugin gratuit est ouvert à contributions (améliorations et/ou corrections). N'hésitez pas à soumettre vos pull-requests sur <a href="https://github.com/hugoKs3/plugin-linksys" target="_blank">Github</a>

# Credits

Ce pugin s'est inspiré des travaux suivants :

-   [reujab](https://github.com/reujab)  via sa librairie Go pour JNAP :  [linksys](https://github.com/reujab/linksys)

# Disclaimer

-   Ce plugin ne prétend pas être exempt de bugs.
-   Ce plugin vous est fourni sans aucune garantie. Bien que peu probable, si il venait à corrompre votre installation Jeedom ou routeur Linksys, l'auteur ne pourrait en être tenu pour responsable.

# ChangeLog
Disponible [ici](./changelog.html).
