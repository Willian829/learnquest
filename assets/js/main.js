// LearnQuest — main.js

// ===== XP Toast =====
function showXPToast(amount) {
  const t = document.getElementById('xp-toast');
  if (!t) return;
  t.textContent = `+${amount} XP ✨`;
  t.classList.remove('hidden');
  setTimeout(() => t.classList.add('hidden'), 2100);
}

// ===== Achievement Toast =====
function showAchievementToast(icon, title, desc) {
  const t = document.getElementById('achievement-toast');
  if (!t) return;
  t.innerHTML = `
    <div class="ach-toast-icon">${icon}</div>
    <div class="ach-toast-body">
      <div class="ach-toast-title">🏅 Conquista Desbloqueada!</div>
      <div class="ach-toast-sub">${icon} ${title} — ${desc}</div>
    </div>`;
  t.classList.remove('hidden');
  setTimeout(() => t.classList.add('hidden'), 4000);
}

// ===== Complete Mission via fetch =====
function completeMission(missionId, btn) {
  fetch('/learnquest/api/complete_mission.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ mission_id: missionId })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      const card = btn.closest('.mission-card');
      card.classList.add('done');
      card.querySelector('.mission-xp').textContent = '✓ Feito';
      card.onclick = null;
      showXPToast(data.xp_reward);
      updateHeaderStats(data.user);
      if (data.achievements && data.achievements.length) {
        data.achievements.forEach(a => showAchievementToast(a.icon, a.title, a.description));
      }
    }
  }).catch(console.error);
}

// ===== Update header stats live =====
function updateHeaderStats(user) {
  if (!user) return;
  const chips = document.querySelectorAll('.stat-chip span');
  if (chips[0]) chips[0].textContent = user.streak;
  if (chips[1]) chips[1].textContent = user.gems;
  if (chips[2]) chips[2].textContent = user.hearts;
  // XP bar
  const fill = document.querySelector('.xp-fill');
  if (fill && user.xp !== undefined) {
    const xpInLv = user.xp % 200;
    fill.style.width = Math.min((xpInLv / 200) * 100, 100) + '%';
  }
  const pts = document.querySelector('.xp-labels .pts');
  if (pts && user.xp !== undefined) pts.textContent = `${user.xp} / ${(user.level * 200)} XP`;
  const lv = document.querySelector('.xp-labels .lv');
  if (lv && user.level) lv.textContent = `Nível ${user.level}`;
  const badge = document.querySelector('.level-badge');
  if (badge && user.level) badge.textContent = user.level;
}

// ===== Animate XP bar on load =====
document.addEventListener('DOMContentLoaded', () => {
  const fill = document.querySelector('.xp-fill');
  if (fill) {
    const target = fill.style.width || '0%';
    fill.style.width = '0%';
    setTimeout(() => { fill.style.width = target; }, 120);
  }
});
