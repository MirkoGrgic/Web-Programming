let allData = [];
let currentPlaylist = [];
let savedPlaylists = [];
let playlistCounter = 0;

/* CSV */
Papa.parse("/data/netflix_titles.csv", {
    download: true,
    header: true,
    complete: function(results) {
        allData = results.data.filter(item => item.title);
        displayData(allData.slice(0, 10)); 
    }
});

/* TABLICA */
function displayData(data) {
    const tbody = document.querySelector("#dataTable tbody");
    tbody.innerHTML = "";

    data.forEach((item, index) => {
        const row = document.createElement("tr");

        row.innerHTML = `
            <td>${item.title}</td>
            <td>${item.type}</td>
            <td>${item.release_year}</td>
            <td>${item.listed_in}</td>
            <td>
                <button class="add-btn">＋</button>
            </td>
        `;

        
        const btn = row.querySelector(".add-btn");
        btn.addEventListener("click", () => {
            addToPlaylist(item);
        });

        tbody.appendChild(row);
    });
}

/* FILTER */
function filterData() {
    const genre = document.getElementById("genreFilter").value.toLowerCase();
    const yearFrom = document.getElementById("yearFrom").value;
    const yearTo = document.getElementById("yearTo").value;

    let filtered = allData.filter(item => {
        return (!genre || item.listed_in.toLowerCase().includes(genre)) &&
               (!yearFrom || item.release_year >= yearFrom) &&
               (!yearTo || item.release_year <= yearTo);
    });

    displayData(filtered.slice(0, 20));
}

/* ===================== PLAYLIST ===================== */


function addToPlaylist(item) {
    currentPlaylist.push(item);
    renderCurrentPlaylist();
}

function renderCurrentPlaylist() {
    const list = document.getElementById("playlist");
    list.innerHTML = "";

    currentPlaylist.forEach((item, index) => {
        const li = document.createElement("li");
        li.innerHTML = `
            ${item.title}
            <button onclick="removeFromPlaylist(${index})">❌</button>
        `;
        list.appendChild(li);
    });
}


function removeFromPlaylist(index) {
    currentPlaylist.splice(index, 1);
    renderCurrentPlaylist();
}

/* ===================== SPREMI PLAYLISTU ===================== */

function savePlaylist() {
    if (currentPlaylist.length === 0) {
        alert("Nema pjesama u playlisti!");
        return;
    }

    const newPlaylist = {
        id: playlistCounter++,
        name: "Playlist " + playlistCounter,
        songs: [...currentPlaylist]
    };

    savedPlaylists.push(newPlaylist);

    currentPlaylist = [];
    renderCurrentPlaylist();
    renderSavedPlaylists();

    alert("✅ Playlista spremljena!");
}



function renderSavedPlaylists() {
    const container = document.getElementById("savedPlaylists");
    container.innerHTML = "";

    savedPlaylists.forEach(pl => {
        const div = document.createElement("div");
        div.className = "playlist-box";

        div.innerHTML = `
            <h3 onclick="togglePlaylist(${pl.id})">
                📁 ${pl.name} (${pl.songs.length})
            </h3>
            <ul id="pl-${pl.id}" style="display:none;">
                ${pl.songs.map(s => `<li>${s.title}</li>`).join("")}
            </ul>
            <button onclick="deletePlaylist(${pl.id})">🗑 Delete</button>
        `;

        container.appendChild(div);
    });
}

/* otvori/zatvori playlistu */
function togglePlaylist(id) {
    const el = document.getElementById("pl-" + id);
    el.style.display = el.style.display === "none" ? "block" : "none";
}

/* brisanje playlisti */
function deletePlaylist(id) {
    savedPlaylists = savedPlaylists.filter(p => p.id !== id);
    renderSavedPlaylists();
}