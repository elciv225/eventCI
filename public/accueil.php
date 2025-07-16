<?php ?>

<main class="page-container">
    <div class="mobile-search-section">
        <div class="search-container"><input type="text" placeholder="Rechercher des événements"
                                             class="search-input" style="height: 3rem; padding-left: 3rem;"/>
            <div class="search-icon" style="left: 1rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                     viewBox="0 0 256 256">
                    <path d="M229.66,218.34l-50.07-50.06a88.11,88.11,0,1,0-11.31,11.31l50.06,50.07a8,8,0,0,0,11.32-11.32ZM40,112a72,72,0,1,1,72,72A72.08,72.08,0,0,1,40,112Z"></path>
                </svg>
            </div>
        </div>
    </div>
    <div class="filter-section">
        <button class="filter-btn">Date</button>
        <button class="filter-btn">Catégorie</button>
        <button class="filter-btn">Lieu</button>
    </div>

    <section class="events-section">
        <h2 class="section-title">Événements à venir</h2>
        <div class="horizontal-scroll-container" id="upcoming-events-carousel" data-section-carousel>
            <button class="horizontal-scroll-nav prev">&lt;</button>
            <button class="horizontal-scroll-nav next">&gt;</button>
            <div class="event-card event-card-horizontal">
                <div class="event-card-image-wrapper aspect-video">
                    <div class="event-card-carousel" data-carousel>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1511795409834-ef04bbd61622?q=80&w=2670&auto=format&fit=crop"
                                 alt="Soirée musique live"/>
                        </div>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1470225620780-dba8ba36b745?q=80&w=2670&auto=format&fit=crop"
                                 alt="Soirée musique live"/>
                        </div>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1501281668745-f7f57925c3b4?q=80&w=2670&auto=format&fit=crop"
                                 alt="Soirée musique live"/>
                        </div>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1429962714451-bb934ecdc4ec?q=80&w=2670&auto=format&fit=crop"
                                 alt="Soirée musique live"/>
                        </div>
                    </div>
                    <button class="carousel-arrow prev">&lt;</button>
                    <button class="carousel-arrow next">&gt;</button>
                </div>
                <a href="#">
                    <div><h3 class="event-card-title">Soirée musique live</h3>
                        <p class="event-card-desc">Profitez d'une soirée de musique live avec des groupes
                            locaux.</p>
                    </div>
                </a>
            </div>
            <div class="event-card event-card-horizontal">
                <div class="event-card-image-wrapper aspect-video">
                    <div class="event-card-carousel" data-carousel>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1523580494863-6f3031224c94?q=80&w=2670&auto=format&fit=crop"
                                 alt="Expo art et culture"/>
                        </div>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1594122230689-45899d9e6f69?q=80&w=2670&auto=format&fit=crop"
                                 alt="Expo art et culture"/>
                        </div>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1605429523419-d828acb941d9?q=80&w=2670&auto=format&fit=crop"
                                 alt="Expo art et culture"/>
                        </div>
                    </div>
                    <button class="carousel-arrow prev">&lt;</button>
                    <button class="carousel-arrow next">&gt;</button>
                </div>
                <a href="#">
                    <div><h3 class="event-card-title">Expo art et culture</h3>
                        <p class="event-card-desc">Explorez l'art contemporain et les expositions culturelles.</p>
                    </div>
                </a>
            </div>
            <div class="event-card event-card-horizontal">
                <div class="event-card-image-wrapper aspect-video">
                    <div class="event-card-carousel" data-carousel>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1504674900247-0877df9cc836?q=80&w=2670&auto=format&fit=crop"
                                 alt="Goût de la ville"/>
                        </div>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1498038432885-c6f3f1b912ee?q=80&w=2574&auto=format&fit=crop"
                                 alt="Goût de la ville"/>
                        </div>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1519167758481-83f550bb49b6?q=80&w=2574&auto=format&fit=crop"
                                 alt="Goût de la ville"/>
                        </div>
                    </div>
                    <button class="carousel-arrow prev">&lt;</button>
                    <button class="carousel-arrow next">&gt;</button>
                </div>
                <a href="#">
                    <div><h3 class="event-card-title">Goût de la ville</h3>
                        <p class="event-card-desc">Découvrez les meilleurs plats et boissons que la ville a à
                            offrir.</p></div>
                </a>
            </div>
        </div>
    </section>

    <section class="events-section">
        <h2 class="section-title">Recommandé pour vous</h2>
        <div class="events-grid" id="recommended-grid" data-section-carousel>
            <div class="event-card">
                <div class="event-card-image-wrapper aspect-square">
                    <div class="event-card-carousel" data-carousel>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1505236858219-8359eb29e329?q=80&w=2562&auto=format&fit=crop"
                                 alt="Séance de yoga en plein air"/>
                        </div>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1475724017904-b712052c192a?q=80&w=2670&auto=format&fit=crop"
                                 alt="Séance de yoga en plein air"/>
                        </div>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1599901860904-17e6ed7083a0?q=80&w=2670&auto=format&fit=crop"
                                 alt="Séance de yoga en plein air"/>
                        </div>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1518611012118-696072aa579a?q=80&w=2670&auto=format&fit=crop"
                                 alt="Séance de yoga en plein air"/>
                        </div>
                    </div>
                    <button class="carousel-arrow prev">&lt;</button>
                    <button class="carousel-arrow next">&gt;</button>
                </div>
                <a href="?info-event=1">
                    <div><h3 class="event-card-title">Séance de yoga en plein air</h3>
                        <p class="event-card-desc">Rejoignez une séance de yoga relaxante dans le parc.</p></div>
                </a>
            </div>
            <div class="event-card">
                <div class="event-card-image-wrapper aspect-square">
                    <div class="event-card-carousel" data-carousel>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?q=80&w=2670&auto=format&fit=crop"
                                 alt="Événement de dégustation de vin"/>
                        </div>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1587880915128-3879504f26b1?q=80&w=2574&auto=format&fit=crop"
                                 alt="Événement de dégustation de vin"/>
                        </div>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1566633806327-68e152aaf26d?q=80&w=2670&auto=format&fit=crop"
                                 alt="Événement de dégustation de vin"/>
                        </div>
                    </div>
                    <button class="carousel-arrow prev">&lt;</button>
                    <button class="carousel-arrow next">&gt;</button>
                </div>
                <a href="#">
                    <div><h3 class="event-card-title">Événement de dégustation de vin</h3>
                        <p class="event-card-desc">Dégustez une variété de vins du monde entier.</p></div>
                </a>
            </div>
            <div class="event-card">
                <div class="event-card-image-wrapper aspect-square">
                    <div class="event-card-carousel" data-carousel>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1518005020951-eccb494ad742?q=80&w=2448&auto=format&fit=crop"
                                 alt="Atelier de photographie"/>
                        </div>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1509343256512-d77a5cb3791b?q=80&w=2670&auto=format&fit=crop"
                                 alt="Atelier de photographie"/>
                        </div>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1554048612-b6a482bc67e5?q=80&w=2670&auto=format&fit=crop"
                                 alt="Atelier de photographie"/>
                        </div>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1542038784456-1ea8e935640e?q=80&w=2670&auto=format&fit=crop"
                                 alt="Atelier de photographie"/>
                        </div>
                    </div>
                    <button class="carousel-arrow prev">&lt;</button>
                    <button class="carousel-arrow next">&gt;</button>
                </div>
                <a href="#">
                    <div><h3 class="event-card-title">Atelier de photographie</h3>
                        <p class="event-card-desc">Apprenez des techniques de photographie avec un
                            professionnel.</p>
                    </div>
                </a>
            </div>
            <div class="event-card">
                <div class="event-card-image-wrapper aspect-square">
                    <div class="event-card-carousel" data-carousel>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1495446815901-a7297e633e8d?q=80&w=2670&auto=format&fit=crop"
                                 alt="Réunion du club de lecture"/>
                        </div>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1524103416202-DE46662137a5?q=80&w=2574&auto=format&fit=crop"
                                 alt="Réunion du club de lecture"/>
                        </div>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1513475382585-d06e58bcb0e0?q=80&w=2670&auto=format&fit=crop"
                                 alt="Réunion du club de lecture"/>
                        </div>
                    </div>
                    <button class="carousel-arrow prev">&lt;</button>
                    <button class="carousel-arrow next">&gt;</button>
                </div>
                <a href="#">
                    <div><h3 class="event-card-title">Réunion du club de lecture</h3>
                        <p class="event-card-desc">Discutez des derniers best-sellers avec d'autres amateurs de
                            livres.</p></div>
                </a>
            </div>
            <div class="event-card">
                <div class="event-card-image-wrapper aspect-square">
                    <div class="event-card-carousel" data-carousel>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1607082349566-187342175e2f?q=80&w=2670&auto=format&fit=crop"
                                 alt="Marché des artisans"/>
                        </div>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1549448508-4c541c5317b1?q=80&w=2574&auto=format&fit=crop"
                                 alt="Marché des artisans"/>
                        </div>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1513519245088-0e12902e5a38?q=80&w=2670&auto=format&fit=crop"
                                 alt="Marché des artisans"/>
                        </div>
                    </div>
                    <button class="carousel-arrow prev">&lt;</button>
                    <button class="carousel-arrow next">&gt;</button>
                </div>
                <a href="#">
                    <div><h3 class="event-card-title">Marché des artisans</h3>
                        <p class="event-card-desc">Découvrez des créations uniques faites par des artisans
                            locaux.</p>
                    </div>
                </a>
            </div>
            <div class="event-card">
                <div class="event-card-image-wrapper aspect-square">
                    <div class="event-card-carousel" data-carousel>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1579027989536-b7b1f875659b?q=80&w=2574&auto=format&fit=crop"
                                 alt="Festival de cinéma en plein air"/>
                        </div>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1531058020387-3be344556be6?q=80&w=2574&auto=format&fit=crop"
                                 alt="Festival de cinéma en plein air"/>
                        </div>
                    </div>
                    <button class="carousel-arrow prev">&lt;</button>
                    <button class="carousel-arrow next">&gt;</button>
                </div>
                <a href="#">
                    <div><h3 class="event-card-title">Festival de cinéma en plein air</h3>
                        <p class="event-card-desc">Regardez des classiques du cinéma sous les étoiles.</p></div>
                </a>
            </div>
            <div class="event-card">
                <div class="event-card-image-wrapper aspect-square">
                    <div class="event-card-carousel" data-carousel>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1533174072545-7a4b6ad7a6c3?q=80&w=2670&auto=format&fit=crop"
                                 alt="Concert de rock"/>
                        </div>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1501612780327-45045538702b?q=80&w=2670&auto=format&fit=crop"
                                 alt="Concert de rock"/>
                        </div>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1470229722913-7c0e2dbbafd3?q=80&w=2670&auto=format&fit=crop"
                                 alt="Concert de rock"/>
                        </div>
                    </div>
                    <button class="carousel-arrow prev">&lt;</button>
                    <button class="carousel-arrow next">&gt;</button>
                </div>
                <a href="#">
                    <div><h3 class="event-card-title">Concert de rock</h3>
                        <p class="event-card-desc">Vibrez au son des meilleurs groupes de rock de la région.</p>
                    </div>
                </a>
            </div>
            <div class="event-card">
                <div class="event-card-image-wrapper aspect-square">
                    <div class="event-card-carousel" data-carousel>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1486427944299-d1955d23e34d?q=80&w=2670&auto=format&fit=crop"
                                 alt="Cours de cuisine italienne"/>
                        </div>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1507048331197-7d4ac70811cf?q=80&w=2574&auto=format&fit=crop"
                                 alt="Cours de cuisine italienne"/>
                        </div>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1556910103-1c02745aae4d?q=80&w=2670&auto=format&fit=crop"
                                 alt="Cours de cuisine italienne"/>
                        </div>
                    </div>
                    <button class="carousel-arrow prev">&lt;</button>
                    <button class="carousel-arrow next">&gt;</button>
                </div>
                <a href="#">
                    <div><h3 class="event-card-title">Cours de cuisine italienne</h3>
                        <p class="event-card-desc">Apprenez à préparer des pâtes fraîches comme un chef.</p></div>
                </a>
            </div>
            <div class="event-card">
                <div class="event-card-image-wrapper aspect-square">
                    <div class="event-card-carousel" data-carousel>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1472653431158-6364773b2a56?q=80&w=2574&auto=format&fit=crop"
                                 alt="Randonnée guidée"/>
                        </div>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1551632811-561732d1e306?q=80&w=2670&auto=format&fit=crop"
                                 alt="Randonnée guidée"/>
                        </div>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1501555088652-021faa106b9b?q=80&w=2673&auto=format&fit=crop"
                                 alt="Randonnée guidée"/>
                        </div>
                    </div>
                    <button class="carousel-arrow prev">&lt;</button>
                    <button class="carousel-arrow next">&gt;</button>
                </div>
                <a href="#">
                    <div><h3 class="event-card-title">Randonnée guidée</h3>
                        <p class="event-card-desc">Explorez les sentiers naturels avec un guide expérimenté.</p>
                    </div>
                </a>
            </div>
            <div class="event-card">
                <div class="event-card-image-wrapper aspect-square">
                    <div class="event-card-carousel" data-carousel>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1517457373958-b7bdd4587205?q=80&w=2669&auto=format&fit=crop"
                                 alt="Atelier de fitness"/>
                        </div>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?q=80&w=2670&auto=format&fit=crop"
                                 alt="Atelier de fitness"/>
                        </div>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1526506118085-60ce8714f8c5?q=80&w=2674&auto=format&fit=crop"
                                 alt="Atelier de fitness"/>
                        </div>
                    </div>
                    <button class="carousel-arrow prev">&lt;</button>
                    <button class="carousel-arrow next">&gt;</button>
                </div>
                <a href="#">
                    <div><h3 class="event-card-title">Atelier de fitness</h3>
                        <p class="event-card-desc">Rejoignez notre atelier de fitness pour améliorer votre santé et
                            votre bien-être.</p></div>
                </a>
            </div>
            <div class="event-card">
                <div class="event-card-image-wrapper aspect-square">
                    <div class="event-card-carousel" data-carousel>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1511988617509-a57c8a288659?q=80&w=2671&auto=format&fit=crop"
                                 alt="Festival de musique électronique"/>
                        </div>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1505373877841-8d25f7d46678?q=80&w=2612&auto=format&fit=crop"
                                 alt="Festival de musique électronique"/>
                        </div>
                        <div class="event-card-image">
                            <img src="https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?q=80&w=2670&auto=format&fit=crop"
                                 alt="Festival de musique électronique"/>
                        </div>
                    </div>
                    <button class="carousel-arrow prev">&lt;</button>
                    <button class="carousel-arrow next">&gt;</button>
                </div>
                <a href="#">
                    <div><h3 class="event-card-title">Festival de musique électronique</h3>
                        <p class="event-card-desc">Dansez toute la nuit sur les meilleurs beats électroniques.</p>
                    </div>
                </a>
            </div>
        </div>
        <div class="see-more-container" id="see-more-container">
            <a href="#" class="see-more-link" id="see-more-link">Voir plus</a>
        </div>
    </section>
</main>