        (() => {
            'use strict';

            // Theme Manager
            const ThemeManager = {
                currentTheme: 'dark',
                toggle: null,
                icon: null,

                init() {
                    this.toggle = document.getElementById('themeToggle');
                    this.icon = this.toggle?.querySelector('i');
                    
                    this.currentTheme = localStorage.getItem('financas-theme') || 'dark';
                    this.apply(this.currentTheme);

                    this.toggle?.addEventListener('click', () => this.switch());
                },

                apply(theme) {
                    this.currentTheme = theme;
                    const html = document.documentElement;
                    
                    if (theme === 'light') {
                        html.classList.add('light-theme');
                        if (this.icon) {
                            this.icon.className = 'bi bi-sun';
                        }
                    } else {
                        html.classList.remove('light-theme');
                        if (this.icon) {
                            this.icon.className = 'bi bi-moon-stars';
                        }
                    }

                    ChartManager.updateColors(theme);
                },

                switch() {
                    const newTheme = this.currentTheme === 'dark' ? 'light' : 'dark';
                    localStorage.setItem('financas-theme', newTheme);
                    this.apply(newTheme);
                }
            };

            // Stats Counter Animation
            const StatsCounter = {
                init() {
                    const stats = document.querySelectorAll('.stat-value[data-count]');
                    
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                this.animateCounter(entry.target);
                                observer.unobserve(entry.target);
                            }
                        });
                    }, { threshold: 0.5 });

                    stats.forEach(stat => observer.observe(stat));
                },

                animateCounter(element) {
                    const target = parseInt(element.dataset.count);
                    const duration = 2000;
                    const startTime = performance.now();

                    const animate = (currentTime) => {
                        const elapsed = currentTime - startTime;
                        const progress = Math.min(elapsed / duration, 1);
                        const easeOutQuart = 1 - Math.pow(1 - progress, 4);
                        const current = Math.round(easeOutQuart * target);
                        
                        element.textContent = current.toLocaleString('pt-BR');

                        if (progress < 1) {
                            requestAnimationFrame(animate);
                        } else {
                            element.textContent = target.toLocaleString('pt-BR') + '+';
                        }
                    };

                    requestAnimationFrame(animate);
                }
            };

            // Chart Manager
            const ChartManager = {
                semanalChart: null,
                mensalChart: null,

                init() {
                    this.createCharts();
                },

                createCharts() {
                    const isDark = ThemeManager.currentTheme === 'dark';
                    const colors = isDark 
                        ? ['#ff6600', '#007bff', '#ffc107', '#28a745']
                        : ['#ff7a00', '#2b78c5', '#e89b2e', '#2ea85b'];

                    const chartOptions = {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 15,
                                    font: { size: 13, weight: '600' },
                                    color: isDark ? '#f5f5f7' : '#1a1a1d',
                                    usePointStyle: true,
                                    pointStyle: 'circle'
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                padding: 12,
                                cornerRadius: 8,
                                titleFont: { size: 14, weight: 'bold' },
                                bodyFont: { size: 13 }
                            }
                        },
                        animation: {
                            duration: 800,
                            easing: 'easeInOutQuart'
                        }
                    };

                    // Gráfico Semanal
                    const ctxSemanal = document.getElementById('graficoSemanal')?.getContext('2d');
                    if (ctxSemanal) {
                        this.semanalChart = new Chart(ctxSemanal, {
                            type: 'doughnut',
                            data: {
                                labels: ['Alimentação', 'Transporte', 'Lazer', 'Outros'],
                                datasets: [{
                                    data: [150, 80, 60, 40],
                                    backgroundColor: colors,
                                    borderWidth: 0,
                                    hoverOffset: 8
                                }]
                            },
                            options: chartOptions
                        });
                    }

                    // Gráfico Mensal
                    const ctxMensal = document.getElementById('graficoMensal')?.getContext('2d');
                    if (ctxMensal) {
                        this.mensalChart = new Chart(ctxMensal, {
                            type: 'doughnut',
                            data: {
                                labels: ['Alimentação', 'Contas Fixas', 'Lazer', 'Outros'],
                                datasets: [{
                                    data: [600, 800, 300, 200],
                                    backgroundColor: colors,
                                    borderWidth: 0,
                                    hoverOffset: 8
                                }]
                            },
                            options: chartOptions
                        });
                    }
                },

                updateColors(theme) {
                    const colors = theme === 'dark'
                        ? ['#ff6600', '#007bff', '#ffc107', '#28a745']
                        : ['#ff7a00', '#2b78c5', '#e89b2e', '#2ea85b'];
                    
                    const textColor = theme === 'dark' ? '#f5f5f7' : '#1a1a1d';

                    [this.semanalChart, this.mensalChart].forEach(chart => {
                        if (chart) {
                            chart.data.datasets[0].backgroundColor = colors;
                            chart.options.plugins.legend.labels.color = textColor;
                            chart.update('none');
                        }
                    });
                }
            };

            // Year Footer
            const yearElement = document.getElementById('year');
            if (yearElement) {
                yearElement.textContent = new Date().getFullYear();
            }

            // Smooth Scroll for Anchor Links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    const href = this.getAttribute('href');
                    if (href !== '#' && href.length > 1) {
                        e.preventDefault();
                        const target = document.querySelector(href);
                        if (target) {
                            target.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        }
                    }
                });
            });

            // Initialize Everything
            document.addEventListener('DOMContentLoaded', () => {
                ThemeManager.init();
                StatsCounter.init();
                ChartManager.init();
                console.log('✅ Finanças Já initialized');
            });
        })();