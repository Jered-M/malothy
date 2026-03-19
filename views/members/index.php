<?php ob_start(); ?>

<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Gestion des Membres</h1>
            <p class="text-slate-500 font-medium">Consultez et gérez la base de données des membres de l'église.</p>
        </div>
        <a href="/index.php?controller=member&action=add" class="inline-flex items-center justify-center px-6 py-3 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-2xl shadow-lg shadow-brand-100 transition-all transform hover:-translate-y-1">
            <i class="fas fa-plus mr-2 text-sm opacity-70"></i> 
            Ajouter un membre
        </a>
    </div>

    <!-- Filtre Card -->
    <div class="bg-white rounded-[2rem] shadow-premium border border-slate-100 p-8 overflow-hidden relative">
        <div class="absolute top-0 right-0 p-4 opacity-5">
            <i class="fas fa-search text-6xl text-slate-900"></i>
        </div>
        <form method="GET" class="relative grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
            <input type="hidden" name="controller" value="member">
            <input type="hidden" name="action" value="index">
            
            <div class="space-y-2">
                <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Recherche</label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-brand-500 transition-colors">
                        <i class="fas fa-search"></i>
                    </div>
                    <input type="text" name="search" value="<?php echo sanitize($searchTerm); ?>" 
                        class="w-full pl-11 pr-4 py-3 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-brand-500 transition-all text-slate-700 placeholder:text-slate-400 font-medium" 
                        placeholder="Nom, email, mobile...">
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Statut</label>
                <select name="status" class="w-full px-4 py-3 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-brand-500 transition-all text-slate-700 font-medium appearance-none">
                    <option value="">Tous les statuts</option>
                    <option value="actif" <?php echo $status === 'actif' ? 'selected' : ''; ?>>Actif</option>
                    <option value="inactif" <?php echo $status === 'inactif' ? 'selected' : ''; ?>>Inactif</option>
                </select>
            </div>

            <div class="space-y-2">
                <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Département</label>
                <select name="department" class="w-full px-4 py-3 bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-brand-500 transition-all text-slate-700 font-medium appearance-none">
                    <option value="">Tous les départements</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo sanitize($dept); ?>" <?php echo $department === $dept ? 'selected' : ''; ?>>
                            <?php echo sanitize($dept); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="bg-slate-900 text-white font-bold py-3 px-6 rounded-xl hover:bg-slate-800 transition-all shadow-lg shadow-slate-200 flex items-center justify-center">
                <i class="fas fa-filter mr-2 text-xs opacity-70"></i> Filtrer
            </button>
        </form>
    </div>

    <!-- Table Container -->
    <div class="bg-white rounded-[2rem] shadow-premium border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-50">
                        <th class="px-8 py-5 text-xs font-black text-slate-400 uppercase tracking-widest">Identité</th>
                        <th class="px-6 py-5 text-xs font-black text-slate-400 uppercase tracking-widest">Contact</th>
                        <th class="px-6 py-5 text-xs font-black text-slate-400 uppercase tracking-widest font-sans">Département</th>
                        <th class="px-6 py-5 text-xs font-black text-slate-400 uppercase tracking-widest">État</th>
                        <th class="px-8 py-5 text-center text-xs font-black text-slate-400 uppercase tracking-widest">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php foreach ($members as $member): ?>
                        <tr class="group hover:bg-slate-50/50 transition-colors">
                            <td class="px-8 py-5">
                                <div class="flex items-center space-x-4">
                                    <div class="w-12 h-12 bg-slate-100 rounded-2xl flex items-center justify-center shrink-0 overflow-hidden border-2 border-white shadow-sm group-hover:scale-105 transition-transform">
                                        <?php if ($member['photo']): ?>
                                            <img src="<?php echo sanitize($member['photo']); ?>" alt="" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <span class="text-slate-400 font-bold text-lg"><?php echo strtoupper(substr($member['first_name'], 0, 1)); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800"><?php echo sanitize($member['first_name'] . ' ' . $member['last_name']); ?></p>
                                        <p class="text-[10px] text-slate-400 font-medium uppercase tracking-tighter italic">Inscrit le <?php echo formatDate($member['created_at'] ?? date('Y-m-d')); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="space-y-1">
                                    <div class="flex items-center text-xs font-medium text-slate-600">
                                        <i class="fas fa-envelope mr-2 w-4 text-slate-300"></i>
                                        <?php echo sanitize($member['email']); ?>
                                    </div>
                                    <div class="flex items-center text-xs font-medium text-slate-600">
                                        <i class="fas fa-phone mr-2 w-4 text-slate-300"></i>
                                        <?php echo sanitize($member['phone']); ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <span class="px-3 py-1 bg-slate-100 text-slate-600 rounded-lg text-xs font-bold ring-1 ring-slate-200">
                                    <?php echo sanitize($member['department'] ?? 'Général'); ?>
                                </span>
                            </td>
                            <td class="px-6 py-5">
                                <?php if ($member['status'] === 'actif'): ?>
                                    <span class="inline-flex items-center px-3 py-1 bg-emerald-50 text-emerald-600 rounded-full text-xs font-bold ring-1 ring-emerald-100">
                                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full mr-2"></span>
                                        Actif
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-3 py-1 bg-slate-100 text-slate-400 rounded-full text-xs font-bold ring-1 ring-slate-200">
                                        <span class="w-1.5 h-1.5 bg-slate-400 rounded-full mr-2"></span>
                                        Inactif
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-8 py-5 text-center">
                                <div class="flex items-center justify-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <a href="/index.php?controller=member&action=details&id=<?php echo $member['id']; ?>" 
                                        class="w-8 h-8 flex items-center justify-center bg-white rounded-lg text-slate-600 border border-slate-100 hover:bg-brand-50 hover:text-brand-600 hover:border-brand-200 transition-all shadow-sm" title="Détails">
                                        <i class="fas fa-eye text-xs"></i>
                                    </a>
                                    <a href="/index.php?controller=member&action=edit&id=<?php echo $member['id']; ?>" 
                                        class="w-8 h-8 flex items-center justify-center bg-white rounded-lg text-slate-600 border border-slate-100 hover:bg-emerald-50 hover:text-emerald-600 hover:border-emerald-200 transition-all shadow-sm" title="Modifier">
                                        <i class="fas fa-edit text-xs"></i>
                                    </a>
                                    <form action="/index.php?controller=member&action=delete" method="POST" class="inline"
                                        onsubmit="return confirm('Souhaitez-vous vraiment retirer ce membre ? Cette action est irréversible.')">
                                        <input type="hidden" name="id" value="<?php echo $member['id']; ?>">
                                        <button type="submit" class="w-8 h-8 flex items-center justify-center bg-white rounded-lg text-slate-400 border border-slate-100 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-all shadow-sm" title="Supprimer">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (empty($members)): ?>
            <div class="flex flex-col items-center justify-center py-20 px-8 text-center">
                <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mb-6 border-2 border-dashed border-slate-200">
                    <i class="fas fa-user-slash text-3xl text-slate-300"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-800 mb-2">Aucun membre trouvé</h3>
                <p class="text-slate-500 max-w-sm">Désolé, nous n'avons trouvé aucun membre correspondant à vos critères de recherche.</p>
                <a href="/index.php?controller=member&action=index" class="mt-8 text-brand-600 font-bold hover:underline">Réinitialiser les filtres</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>

