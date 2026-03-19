<?php ob_start(); ?>

<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Enregistrement des Dîmes</h1>
        <a href="/index.php?controller=finance&action=addTithe" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">
            <i class="fas fa-plus mr-2"></i> Nouvelle dîme
        </a>
    </div>

    <!-- Filtre -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="hidden" name="controller" value="finance">
            <input type="hidden" name="action" value="tithes">
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Membre</label>
                <select name="member_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">Tous</option>
                    <?php foreach ($members as $member): ?>
                        <option value="<?php echo $member['id']; ?>" <?php echo $memberId == $member['id'] ? 'selected' : ''; ?>>
                            <?php echo sanitize($member['first_name'] . ' ' . $member['last_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Du</label>
                <input type="date" name="start_date" value="<?php echo sanitize($startDate ?? ''); ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Au</label>
                <input type="date" name="end_date" value="<?php echo sanitize($endDate ?? ''); ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    <i class="fas fa-search mr-2"></i> Filtrer
                </button>
            </div>
        </form>
    </div>

    <!-- Tableau -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Membre</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Montant</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Date</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Commentaire</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tithes as $tithe): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <?php if ($tithe['member_id']): ?>
                                <a href="/index.php?controller=member&action=details&id=<?php echo $tithe['member_id']; ?>" class="text-blue-600 hover:text-blue-700">
                                    <?php echo sanitize($tithe['first_name'] . ' ' . $tithe['last_name']); ?>
                                </a>
                            <?php else: ?>
                                <span class="text-gray-500">Non attribuée</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 font-semibold text-green-600"><?php echo formatMoney($tithe['amount']); ?></td>
                        <td class="px-6 py-4 text-sm"><?php echo formatDate($tithe['tithe_date']); ?></td>
                        <td class="px-6 py-4 text-sm"><?php echo sanitize($tithe['comment'] ?? '-'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (empty($tithes)): ?>
            <div class="text-center py-8 text-gray-500">
                <p>Aucune dîme enregistrée</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
