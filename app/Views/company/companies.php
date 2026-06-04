        <?= view('Layouts/candidate_header', ['title' => 'Local Hiring Companies']) ?>

<?php
$featuredCompanies = is_array($featuredCompanies ?? null) ? $featuredCompanies : [];
$popularRoles = is_array($popularRoles ?? null) ? $popularRoles : [];
$popularCities = is_array($popularCities ?? null) ? $popularCities : [];
$companyCount = count($featuredCompanies);
$openJobCount = array_sum(array_map(static fn ($company): int => (int) ($company['open_jobs'] ?? 0), $featuredCompanies));
?>


<div class="local-company-page">
    <div class="local-company-shell">
        <section class="local-company-hero">
            <div class="local-company-intro">
                <div class="local-company-kicker"><i class="fas fa-building"></i> Local company finder</div>
                <h1>Find companies hiring for your role in your city</h1>
                <p>Search the companies and jobs already inside HireMatrix first, then use company profiles and open-role links to move faster from discovery to application.</p>
            </div>
            <div class="local-company-stats" aria-label="Local company summary">
                <div class="local-company-stat">
                    <strong><?= esc((string) $companyCount) ?></strong>
                    <span>Featured companies</span>
                </div>
                <div class="local-company-stat">
                    <strong><?= esc((string) $openJobCount) ?></strong>
                    <span>Open jobs represented</span>
                </div>
            </div>
        </section>

        <section class="local-company-search" aria-label="Search local hiring companies">
            <div class="local-company-search-grid">
                <div class="local-company-field">
                    <label for="role">Role or skill</label>
                    <input type="text" id="role" placeholder="Frontend Developer, PHP, UI UX" autocomplete="off">
                    <div id="roleSuggest" class="local-company-suggestions" hidden></div>
                </div>
                <div class="local-company-field">
                    <label for="city">City</label>
                    <input type="text" id="city" placeholder="Bangalore, Pune, Hyderabad" autocomplete="off">
                    <div id="citySuggest" class="local-company-suggestions" hidden></div>
                </div>
                <button type="button" class="local-company-submit" id="companySearchButton">
                    <i class="fas fa-search"></i>
                    <span>Find Companies</span>
                </button>
            </div>

            <div class="local-company-chips" aria-label="Popular searches">
                <?php foreach (array_slice($popularRoles, 0, 4) as $roleName): ?>
                    <button type="button" class="local-company-chip" data-role="<?= esc($roleName) ?>"><?= esc($roleName) ?></button>
                <?php endforeach; ?>
                <?php foreach (array_slice($popularCities, 0, 4) as $cityName): ?>
                    <button type="button" class="local-company-chip" data-city="<?= esc($cityName) ?>"><?= esc($cityName) ?></button>
                <?php endforeach; ?>
            </div>
        </section>

        <section>
            <div class="local-company-section-head">
                <div>
                    <h2 id="companyListTitle">Featured Local Companies</h2>
                    <p id="companyListSubtitle">A starting point for discovering local employers and active openings.</p>
                </div>
                <a href="<?= base_url('candidate/company-job-discovery') ?>" class="local-company-secondary" style="padding: 9px 13px; border-radius: 8px; text-decoration: none; font-weight: 800;">
                    <i class="fas fa-search-plus"></i> Company Discovery
                </a>
            </div>

            <div class="local-company-grid" id="companyList">
                <?php foreach ($featuredCompanies as $company): ?>
                    <?= view('company/company_card', ['company' => $company]) ?>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>

<script>
const endpoints = {
    fetchCompanies: "<?= base_url('fetch-companies') ?>",
    suggest: "<?= base_url('suggest') ?>"
};

const companyList = document.getElementById("companyList");
const companyListTitle = document.getElementById("companyListTitle");
const companyListSubtitle = document.getElementById("companyListSubtitle");
const searchButton = document.getElementById("companySearchButton");
const roleInput = document.getElementById("role");
const cityInput = document.getElementById("city");

function escapeHtml(value) {
    return String(value ?? "").replace(/[&<>"']/g, function (char) {
        return {
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#039;"
        }[char];
    });
}

function companyInitial(name) {
    const trimmed = String(name || "C").trim();
    return escapeHtml((trimmed.charAt(0) || "C").toUpperCase());
}

function companyCard(company) {
    const name = company.name || "Company";
    const location = company.location || "India";
    const industry = company.industry || "Hiring company";
    const description = company.description || "Explore this company and review current opportunities.";
    const openJobs = Number(company.open_jobs || 0);
    const website = company.website || "";
    const logo = company.logo || "";
    const logoHtml = logo
        ? `<img src="${escapeHtml(logo)}" alt="${escapeHtml(name)}" onerror="this.parentElement.textContent='${companyInitial(name)}';">`
        : companyInitial(name);
    const websiteHtml = website
        ? `<div class="local-company-actions"><a href="${escapeHtml(website)}" target="_blank" rel="noopener" class="local-company-primary"><i class="fas fa-external-link-alt"></i> Website</a></div>`
        : "";

    return `
        <article class="local-company-card">
            <div class="local-company-card-top">
                <div class="local-company-logo">${logoHtml}</div>
                <div class="local-company-name">
                    <h3 title="${escapeHtml(name)}">${escapeHtml(name)}</h3>
                    <span>${escapeHtml(industry)}</span>
                </div>
            </div>
            <div class="local-company-meta">
                <span class="local-company-pill"><i class="fas fa-map-marker-alt"></i> ${escapeHtml(location)}</span>
                <span class="local-company-pill"><i class="fas fa-briefcase"></i> ${openJobs > 0 ? openJobs + " open" : "Verify openings"}</span>
            </div>
            <p class="local-company-description">${escapeHtml(description)}</p>
            ${websiteHtml}
        </article>
    `;
}

function setLoading() {
    companyList.innerHTML = `<div class="local-company-loading"><i class="fas fa-spinner fa-spin"></i> Finding relevant companies...</div>`;
}

function setEmpty(role, city) {
    companyList.innerHTML = `
        <div class="local-company-empty">
            <strong>No matching companies found yet.</strong>
            <div>Try a broader role like "Developer" or another nearby city. You can also browse all jobs.</div>
            <div class="mt-3">
                <a href="<?= base_url('jobs') ?>" class="local-company-primary" style="display:inline-flex;padding:10px 14px;border-radius:8px;text-decoration:none;">
                    Browse All Jobs
                </a>
            </div>
        </div>
    `;
    companyListTitle.textContent = "No local matches";
    companyListSubtitle.textContent = `We could not find companies for ${role} in ${city}.`;
}

function searchCompanies() {
    const role = roleInput.value.trim();
    const city = cityInput.value.trim();

    if (!role || !city) {
        alert("Please enter both a role and a city.");
        return;
    }

    searchButton.disabled = true;
    setLoading();

    const params = new URLSearchParams({ role, city });
    fetch(`${endpoints.fetchCompanies}?${params.toString()}`)
        .then(response => {
            if (!response.ok) {
                throw new Error("Search request failed");
            }
            return response.json();
        })
        .then(data => {
            if (!Array.isArray(data) || data.length === 0) {
                setEmpty(role, city);
                return;
            }

            companyListTitle.textContent = `Companies hiring for ${role}`;
            companyListSubtitle.textContent = `Showing employers connected to ${city}.`;
            companyList.innerHTML = data.map(companyCard).join("");
            companyList.scrollIntoView({ behavior: "smooth", block: "start" });
        })
        .catch(() => {
            companyList.innerHTML = `<div class="local-company-empty">Something went wrong while loading companies. Please try again.</div>`;
        })
        .finally(() => {
            searchButton.disabled = false;
        });
}

function setupSuggest(input, box, type) {
    let timer = null;

    input.addEventListener("input", function () {
        const value = input.value.trim();
        clearTimeout(timer);

        if (value.length < 2) {
            box.hidden = true;
            box.innerHTML = "";
            return;
        }

        timer = setTimeout(function () {
            const params = new URLSearchParams({ term: value, type });
            fetch(`${endpoints.suggest}?${params.toString()}`)
                .then(response => response.json())
                .then(items => {
                    box.innerHTML = "";
                    if (!Array.isArray(items) || items.length === 0) {
                        box.hidden = true;
                        return;
                    }

                    items.forEach(item => {
                        const option = document.createElement("button");
                        option.type = "button";
                        option.className = "local-company-suggestion";
                        option.textContent = item;
                        option.addEventListener("click", function () {
                            input.value = item;
                            box.hidden = true;
                            box.innerHTML = "";
                        });
                        box.appendChild(option);
                    });
                    box.hidden = false;
                })
                .catch(() => {
                    box.hidden = true;
                });
        }, 180);
    });

    document.addEventListener("click", function (event) {
        if (!input.contains(event.target) && !box.contains(event.target)) {
            box.hidden = true;
        }
    });
}

document.querySelectorAll(".local-company-chip").forEach(chip => {
    chip.addEventListener("click", function () {
        if (chip.dataset.role) {
            roleInput.value = chip.dataset.role;
        }
        if (chip.dataset.city) {
            cityInput.value = chip.dataset.city;
        }
        if (roleInput.value.trim() && cityInput.value.trim()) {
            searchCompanies();
        }
    });
});

searchButton.addEventListener("click", searchCompanies);
[roleInput, cityInput].forEach(input => {
    input.addEventListener("keydown", function (event) {
        if (event.key === "Enter") {
            event.preventDefault();
            searchCompanies();
        }
    });
});

setupSuggest(roleInput, document.getElementById("roleSuggest"), "role");
setupSuggest(cityInput, document.getElementById("citySuggest"), "city");
</script>

<?= view('Layouts/candidate_footer') ?>
    