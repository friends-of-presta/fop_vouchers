# FOP - Vouchers
Ce module vous permettra de configurer des bons de d'achat toujours  
utilisables même après l'arrêt votre boutique solidaire.

# Comment faire ? :
-  Créez vos produits bons d'achat
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
Mes produit "Bon d'achat" d'achat sont les suivants:
- ID 42 - "Bon d'achat 8€"
- ID 314 - "Bon d'achat 15€"

Je souhaite que mes bons soient valables 6 mois après leur achat.

**La Configuration sera telle que:**
- Liste des produits associés à des bons d'achat : **42;314**
- Durée du bon d'achat : **6;m**


*Arnaud Drieux - Store Commander*