# FOP - Vouchers
Ce module vous permettra de configurer des bons d'achat utilisables  
sur un panier et en physique si vous arrêtez votre boutique solidaire.

# Comment faire ? :
-  Créez vos produits "Bon d'achat"
-  Dans la configuration du module, configurez :
   - les ID produits séparés par un ";"
   - la durée (un nombre) et le type de durée, séparés par un ";"
# Types de durée:
- y: année(s)
- m: mois
- w: semaine(s)
- d: jour(s)
- h: heure(s)

# Exemples :
Mes produits "Bon d'achat" sont les suivants:
- ID 42 - "Bon d'achat 8€"
- ID 314 - "Bon d'achat 15€"

Je souhaite que mes bons soient valables **6 mois** après leur achat.

**La configuration sera telle que:**
- Liste des produits associés à des bons d'achat : **42;314**
- Durée du bon d'achat : **6;m**


*Arnaud Drieux - Store Commander*