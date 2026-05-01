<?php
$file = 'frontend/public/js/pages.js';
$content = file_get_contents($file);

$old = '<h3 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2"><i class="fas fa-credit-card text-brand-500"></i> Passerelle Flutterwave</h3>';

$new = '<div>
                                <h3 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2"><i class="fas fa-mobile-screen-button text-brand-500"></i> Passerelle MaishaPay (Mobile Money)</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="field-label" for="maishapay_public_key">Clé Publique (API Key)</label>
                                        <input id="maishapay_public_key" name="maishapay_public_key" type="text" class="pro-input" placeholder="pk_test_..." value="${settings.maishapay_public_key || \'\'}">
                                    </div>
                                    <div>
                                        <label class="field-label" for="maishapay_secret_key">Clé Secrète (Secret Key)</label>
                                        <input id="maishapay_secret_key" name="maishapay_secret_key" type="password" class="pro-input" placeholder="sk_test_..." value="${settings.maishapay_secret_key || \'\'}">
                                    </div>
                                    <div>
                                        <label class="field-label" for="maishapay_gateway_mode">Mode de la passerelle</label>
                                        <select id="maishapay_gateway_mode" name="maishapay_gateway_mode" class="pro-select">
                                            <option value="0" ${settings.maishapay_gateway_mode === \'0\' || !settings.maishapay_gateway_mode ? \'selected\' : \'\'}>Mode TEST (Sandbox)</option>
                                            <option value="1" ${settings.maishapay_gateway_mode === \'1\' ? \'selected\' : \'\'}>Mode PRODUCTION (Live)</option>
                                        </select>
                                        <p class="pro-helper">Utilisez le mode Sandbox pour vos tests TFC.</p>
                                    </div>
                                </div>
                            </div>

                            <hr class="border-slate-100">

                            <div>
                                <h3 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2"><i class="fas fa-credit-card text-brand-500"></i> Passerelle Flutterwave</h3>';

if (strpos($content, $old) !== false) {
    $content = str_replace($old, $new, $content);
    file_put_contents($file, $content);
    echo "Replacement successful\n";
} else {
    echo "Target string not found\n";
}
