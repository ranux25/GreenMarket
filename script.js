const dynamiquelabel = document.getElementById('label-name');
const dynamiqueinput = document.getElementById('Full-name');
const dynamiquemessage=document.getElementById('message');
const userRole = document.querySelectorAll('input[name="role"]');

userRole.forEach(radio => {
  radio.addEventListener('change', function() {
    if (this.value === 'Producer') {
      dynamiquelabel.innerText = "Nom de l'entreprise";
      dynamiqueinput.placeholder = "ex: Coopérative Souss Bio";
      dynamiquemessage.innerText="Ouvrez votre boutique numérique et vendez vos récoltes directement aux clients.";
    } else {
      dynamiquelabel.innerText = "Nom complet";
      dynamiqueinput.placeholder = "Jean Dupont";
      dynamiquemessage.innerText="Découvrez des produits frais et soutenez l'agriculture locale dès aujourd'hui.";
    }
  });
});
