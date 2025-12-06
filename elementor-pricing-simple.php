<?php
/**
 * Template Name: IPV Pro - Pricing (Elementor)
 * 
 * Template page per Elementor/WoodMart
 * Copia questo codice in: Appearance → Theme File Editor → Add new template
 * Oppure usa in page builder Elementor direttamente
 */

// Shortcode per pricing cards - Usa in Elementor Text Editor widget
function ipv_pricing_cards_shortcode() {
    ob_start();
    ?>
    <div class="ipv-pricing-wrapper" style="max-width: 1200px; margin: 0 auto; padding: 40px 20px;">
        <div class="ipv-pricing-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin-top: 40px;">
            
            <!-- FREE PLAN -->
            <div class="ipv-pricing-card" style="background: white; border-radius: 20px; padding: 40px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); text-align: center; transition: transform 0.3s;">
                <div class="plan-icon" style="width: 80px; height: 80px; border-radius: 20px; background: linear-gradient(135deg, #667eea, #764ba2); margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; font-size: 40px;">
                    🎁
                </div>
                <h3 style="font-size: 28px; font-weight: 700; margin-bottom: 10px; color: #1e293b;">Free</h3>
                <p style="color: #64748b; margin-bottom: 20px;">Perfetto per testare</p>
                <div style="margin: 30px 0;">
                    <span style="font-size: 48px; font-weight: 800; color: #667eea;">€0</span>
                </div>
                <ul style="list-style: none; padding: 0; margin: 30px 0; text-align: left;">
                    <li style="padding: 10px 0; color: #475569;"><span style="color: #10b981; margin-right: 10px;">✓</span> <strong>10 video/mese</strong></li>
                    <li style="padding: 10px 0; color: #475569;"><span style="color: #10b981; margin-right: 10px;">✓</span> Trascrizioni AI</li>
                    <li style="padding: 10px 0; color: #475569;"><span style="color: #10b981; margin-right: 10px;">✓</span> Descrizioni SEO</li>
                    <li style="padding: 10px 0; color: #475569;"><span style="color: #10b981; margin-right: 10px;">✓</span> 1 sito</li>
                    <li style="padding: 10px 0; color: #475569;"><span style="color: #10b981; margin-right: 10px;">✓</span> Support email</li>
                </ul>
                <a href="/carrello/?add-to-cart=XXX" class="button" style="display: block; background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 15px 30px; border-radius: 12px; text-decoration: none; font-weight: 600; transition: transform 0.3s;">
                    Inizia Gratis
                </a>
            </div>

            <!-- BASIC PLAN -->
            <div class="ipv-pricing-card" style="background: white; border-radius: 20px; padding: 40px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); text-align: center; transition: transform 0.3s;">
                <div class="plan-icon" style="width: 80px; height: 80px; border-radius: 20px; background: linear-gradient(135deg, #f093fb, #f5576c); margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; font-size: 40px;">
                    ⭐
                </div>
                <h3 style="font-size: 28px; font-weight: 700; margin-bottom: 10px; color: #1e293b;">Basic</h3>
                <p style="color: #64748b; margin-bottom: 20px;">Per blogger individuali</p>
                <div style="margin: 30px 0;">
                    <span style="font-size: 48px; font-weight: 800; color: #f5576c;">€9,99</span>
                    <span style="font-size: 18px; color: #64748b;">/mese</span>
                </div>
                <ul style="list-style: none; padding: 0; margin: 30px 0; text-align: left;">
                    <li style="padding: 10px 0; color: #475569;"><span style="color: #10b981; margin-right: 10px;">✓</span> <strong>100 video/mese</strong></li>
                    <li style="padding: 10px 0; color: #475569;"><span style="color: #10b981; margin-right: 10px;">✓</span> Trascrizioni illimitate</li>
                    <li style="padding: 10px 0; color: #475569;"><span style="color: #10b981; margin-right: 10px;">✓</span> Descrizioni SEO GPT-4o</li>
                    <li style="padding: 10px 0; color: #475569;"><span style="color: #10b981; margin-right: 10px;">✓</span> Golden Prompt</li>
                    <li style="padding: 10px 0; color: #475569;"><span style="color: #10b981; margin-right: 10px;">✓</span> 1 sito</li>
                    <li style="padding: 10px 0; color: #475569;"><span style="color: #10b981; margin-right: 10px;">✓</span> Support prioritario</li>
                </ul>
                <a href="/carrello/?add-to-cart=YYY" class="button" style="display: block; background: linear-gradient(135deg, #f093fb, #f5576c); color: white; padding: 15px 30px; border-radius: 12px; text-decoration: none; font-weight: 600; transition: transform 0.3s;">
                    Inizia 7 Giorni Gratis
                </a>
            </div>

            <!-- PRO PLAN (Popular) -->
            <div class="ipv-pricing-card popular" style="position: relative; background: white; border-radius: 20px; padding: 40px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); text-align: center; border: 3px solid #6366f1; transform: scale(1.05); transition: transform 0.3s;">
                <div class="popular-badge" style="position: absolute; top: -15px; left: 50%; transform: translateX(-50%); background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; padding: 8px 30px; border-radius: 50px; font-weight: 700; font-size: 12px; letter-spacing: 1px;">
                    PIÙ POPOLARE
                </div>
                <div class="plan-icon" style="width: 80px; height: 80px; border-radius: 20px; background: linear-gradient(135deg, #4facfe, #00f2fe); margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; font-size: 40px;">
                    🚀
                </div>
                <h3 style="font-size: 28px; font-weight: 700; margin-bottom: 10px; color: #1e293b;">Pro</h3>
                <p style="color: #64748b; margin-bottom: 20px;">Massima produttività</p>
                <div style="margin: 30px 0;">
                    <span style="font-size: 48px; font-weight: 800; color: #4facfe;">€19,99</span>
                    <span style="font-size: 18px; color: #64748b;">/mese</span>
                </div>
                <ul style="list-style: none; padding: 0; margin: 30px 0; text-align: left;">
                    <li style="padding: 10px 0; color: #475569;"><span style="color: #10b981; margin-right: 10px;">✓</span> <strong>200 video/mese</strong></li>
                    <li style="padding: 10px 0; color: #475569;"><span style="color: #10b981; margin-right: 10px;">✓</span> Tutto di Basic +</li>
                    <li style="padding: 10px 0; color: #475569;"><span style="color: #10b981; margin-right: 10px;">✓</span> <strong>3 siti</strong></li>
                    <li style="padding: 10px 0; color: #475569;"><span style="color: #10b981; margin-right: 10px;">✓</span> Analytics avanzata</li>
                    <li style="padding: 10px 0; color: #475569;"><span style="color: #10b981; margin-right: 10px;">✓</span> Priority support</li>
                    <li style="padding: 10px 0; color: #475569;"><span style="color: #10b981; margin-right: 10px;">✓</span> Onboarding call</li>
                </ul>
                <a href="/carrello/?add-to-cart=ZZZ" class="button" style="display: block; background: linear-gradient(135deg, #4facfe, #00f2fe); color: white; padding: 15px 30px; border-radius: 12px; text-decoration: none; font-weight: 600; transition: transform 0.3s;">
                    Inizia 7 Giorni Gratis
                </a>
            </div>

            <!-- PREMIUM PLAN -->
            <div class="ipv-pricing-card" style="background: white; border-radius: 20px; padding: 40px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); text-align: center; transition: transform 0.3s;">
                <div class="plan-icon" style="width: 80px; height: 80px; border-radius: 20px; background: linear-gradient(135deg, #43e97b, #38f9d7); margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; font-size: 40px;">
                    ⚡
                </div>
                <h3 style="font-size: 28px; font-weight: 700; margin-bottom: 10px; color: #1e293b;">Premium</h3>
                <p style="color: #64748b; margin-bottom: 20px;">Per agenzie e team</p>
                <div style="margin: 30px 0;">
                    <span style="font-size: 48px; font-weight: 800; color: #43e97b;">€39,99</span>
                    <span style="font-size: 18px; color: #64748b;">/mese</span>
                </div>
                <ul style="list-style: none; padding: 0; margin: 30px 0; text-align: left;">
                    <li style="padding: 10px 0; color: #475569;"><span style="color: #10b981; margin-right: 10px;">✓</span> <strong>500 video/mese</strong></li>
                    <li style="padding: 10px 0; color: #475569;"><span style="color: #10b981; margin-right: 10px;">✓</span> Tutto di Pro +</li>
                    <li style="padding: 10px 0; color: #475569;"><span style="color: #10b981; margin-right: 10px;">✓</span> <strong>5 siti</strong></li>
                    <li style="padding: 10px 0; color: #475569;"><span style="color: #10b981; margin-right: 10px;">✓</span> Golden Prompt custom</li>
                    <li style="padding: 10px 0; color: #475569;"><span style="color: #10b981; margin-right: 10px;">✓</span> Chat support dedicata</li>
                    <li style="padding: 10px 0; color: #475569;"><span style="color: #10b981; margin-right: 10px;">✓</span> White label</li>
                </ul>
                <a href="/contatti/" class="button" style="display: block; background: linear-gradient(135deg, #43e97b, #38f9d7); color: white; padding: 15px 30px; border-radius: 12px; text-decoration: none; font-weight: 600; transition: transform 0.3s;">
                    Contattaci
                </a>
            </div>

        </div>

        <style>
            .ipv-pricing-card:hover {
                transform: translateY(-10px);
                box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            }
            .ipv-pricing-card.popular:hover {
                transform: translateY(-10px) scale(1.05);
            }
            @media (max-width: 768px) {
                .ipv-pricing-grid {
                    grid-template-columns: 1fr;
                    gap: 30px;
                }
                .ipv-pricing-card.popular {
                    transform: scale(1);
                }
            }
        </style>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('ipv_pricing_cards', 'ipv_pricing_cards_shortcode');


// Shortcode per FAQ accordion
function ipv_faq_accordion_shortcode() {
    ob_start();
    ?>
    <div class="ipv-faq-accordion" style="max-width: 800px; margin: 40px auto; padding: 20px;">
        <details style="background: white; margin-bottom: 15px; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
            <summary style="font-weight: 700; font-size: 18px; color: #1e293b; cursor: pointer; list-style: none;">
                <span style="margin-right: 10px;">❓</span> Cosa succede se finisco i crediti?
            </summary>
            <p style="margin-top: 15px; color: #64748b; line-height: 1.6;">
                I crediti si resettano automaticamente il 1° di ogni mese. Se finisci prima, puoi fare upgrade al piano superiore in qualsiasi momento.
            </p>
        </details>

        <details style="background: white; margin-bottom: 15px; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
            <summary style="font-weight: 700; font-size: 18px; color: #1e293b; cursor: pointer; list-style: none;">
                <span style="margin-right: 10px;">❓</span> Posso cancellare in qualsiasi momento?
            </summary>
            <p style="margin-top: 15px; color: #64748b; line-height: 1.6;">
                Sì! Nessun vincolo. Cancelli quando vuoi dal tuo account. Nessuna penale.
            </p>
        </details>

        <details style="background: white; margin-bottom: 15px; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
            <summary style="font-weight: 700; font-size: 18px; color: #1e293b; cursor: pointer; list-style: none;">
                <span style="margin-right: 10px;">❓</span> Le API keys sono al sicuro?
            </summary>
            <p style="margin-top: 15px; color: #64748b; line-height: 1.6;">
                Assolutamente! Tutte le API keys (SupaData, OpenAI) sono gestite sul nostro server. Tu non vedi mai nessuna chiave.
            </p>
        </details>

        <details style="background: white; margin-bottom: 15px; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
            <summary style="font-weight: 700; font-size: 18px; color: #1e293b; cursor: pointer; list-style: none;">
                <span style="margin-right: 10px;">❓</span> Posso usare il piano su più siti?
            </summary>
            <p style="margin-top: 15px; color: #64748b; line-height: 1.6;">
                Dipende dal piano! Free e Basic = 1 sito, Pro = 3 siti, Premium = 5 siti.
            </p>
        </details>

        <details style="background: white; margin-bottom: 15px; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
            <summary style="font-weight: 700; font-size: 18px; color: #1e293b; cursor: pointer; list-style: none;">
                <span style="margin-right: 10px;">❓</span> Come funziona il trial gratuito?
            </summary>
            <p style="margin-top: 15px; color: #64748b; line-height: 1.6;">
                Tutti i piani a pagamento hanno 7 giorni di prova gratuita. Nessuna carta richiesta per il piano Free.
            </p>
        </details>

        <details style="background: white; margin-bottom: 15px; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
            <summary style="font-weight: 700; font-size: 18px; color: #1e293b; cursor: pointer; list-style: none;">
                <span style="margin-right: 10px;">❓</span> Offrite sconti per annuali?
            </summary>
            <p style="margin-top: 15px; color: #64748b; line-height: 1.6;">
                Sì! Pagando annualmente risparmi il 20%. Contattaci per attivare il piano annuale.
            </p>
        </details>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('ipv_faq_accordion', 'ipv_faq_accordion_shortcode');
