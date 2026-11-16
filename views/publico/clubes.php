<?php
/**
 * @var array $clubes
 * @var int   $idTorneo
 */

use Model\ClubPublico;
?>
<div class="mx-auto max-w-6xl px-4 py-10">
    <h1 class="text-3xl font-black tracking-tight text-slate-900">Clubes</h1>

    <?php if (empty($clubes)): ?>
        <p class="mt-8 rounded-lg border border-slate-200 bg-white p-6 text-slate-600">
            Todavía no hay clubes cargados en este torneo.
        </p>
    <?php else: ?>
        <ul class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($clubes as $club): ?>
            <li>
                <a href="/club?id_equipo=<?= (int) $club['id'] ?>"
                   class="flex h-full items-center gap-4 rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-600">
                    <?php if (!empty($club['escudo'])): ?>
                        <img src="<?= htmlspecialchars($club['escudo']) ?>" alt=""
                             width="56" height="56" loading="lazy"
                             class="h-14 w-14 shrink-0 object-contain">
                    <?php else: ?>
                        <span aria-hidden="true"
                              class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm font-bold text-slate-600">
                            <?= htmlspecialchars(ClubPublico::iniciales($club['nombre'])) ?>
                        </span>
                    <?php endif; ?>

                    <span class="min-w-0">
                        <span class="block truncate text-lg font-black uppercase tracking-tight text-slate-900">
                            <?= htmlspecialchars($club['nombre']) ?>
                        </span>
                        <?php if (!empty($club['cancha'])): ?>
                            <span class="block truncate text-sm uppercase text-slate-600">
                                <?= htmlspecialchars($club['cancha']) ?>
                            </span>
                        <?php elseif (!empty($club['ciudad'])): ?>
                            <span class="block truncate text-sm text-slate-600">
                                <?= htmlspecialchars($club['ciudad']) ?>
                            </span>
                        <?php endif; ?>
                    </span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>