const express = require('express');
const path = require('path');
const fs = require('fs');
const app = express();

// Postavi EJS kao view engine
app.set('view engine', 'ejs');

// Poslužuj statičke datoteke iz mape 'public'
app.use(express.static(path.join(__dirname, 'public')));

// Početna ruta -> index_predlozak.html
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'index_predlozak.html'));
});

// Ruta za galeriju
app.get('/slike', (req, res) => {
    const folderPath = path.join(__dirname, 'public', 'images');

    // Pročitaj sve datoteke iz foldera
    const files = fs.readdirSync(folderPath);

    // Filtriraj samo slike i napravi objekte za EJS
    const images = files
        .filter(file => file.endsWith('.jpg') || file.endsWith('.png') || file.endsWith('.svg'))
        .map((file, index) => ({
            url: `/images/${file}`,
            id: `img${index + 1}`,   // img1, img2...
            title: `Slika ${index + 1}`
        }));

    // Pošalji podatke u EJS predložak
    res.render('slike', { images });
});

// Dinamički port za Railway ili lokalno 3000
const PORT = process.env.PORT || 3000;
app.listen(PORT, () => console.log(`Server radi na portu ${PORT}`));