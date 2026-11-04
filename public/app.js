const dialog = document.querySelector('#task-dialog');
const form = document.querySelector('#task-form');
const field = name => form.elements.namedItem(name);
const dateValue = date => new Date(date.getTime() - date.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
function openTask(task = null) {
  form.reset();
  field('action').value = 'save';
  field('id').value = '';
  document.querySelector('#form-error').textContent = '';
  document.querySelector('#dialog-title').textContent = task ? 'To-do bearbeiten' : 'Neues To-do';
  document.querySelector('#delete-task').hidden = !task;
  if (task) Object.entries(task).forEach(([name, value]) => { if (field(name)) field(name).value = value; });
  else {
    field('starts_at').value = dateValue(new Date());
    field('due_at').value = dateValue(new Date(Date.now() + 86400000));
  }
  dialog.showModal();
}
document.querySelector('#add-task').addEventListener('click', () => openTask());
document.querySelector('#close-dialog').addEventListener('click', () => dialog.close());
document.querySelectorAll('[data-task]').forEach(button => button.addEventListener('click', () => openTask(JSON.parse(button.dataset.task))));
document.querySelector('#delete-task').addEventListener('click', () => {
  if (confirm('Dieses To-do wirklich löschen?')) { field('action').value = 'delete'; form.submit(); }
});
form.addEventListener('submit', event => {
  const start = field('starts_at').value, due = field('due_at').value, main = field('main_at').value;
  if (due <= start || (main && main < due)) {
    event.preventDefault();
    document.querySelector('#form-error').textContent = 'Die Abgabe muss nach dem Start liegen. Der Haupttermin darf nicht vor der Abgabe liegen.';
  }
});
let filter = 'active';
function filterTasks() {
  const query = document.querySelector('#search').value.toLocaleLowerCase('de');
  let visible = 0;
  document.querySelectorAll('.task-card').forEach(card => {
    card.hidden = (filter === 'active' && card.dataset.done === '1') || (filter === 'done' && card.dataset.done === '0') || !card.dataset.title.toLocaleLowerCase('de').includes(query);
    if (!card.hidden) visible++;
  });
  document.querySelector('#empty').hidden = visible > 0;
  document.querySelector('#empty-copy').textContent = query ? 'Für diese Suche gibt es keine passenden To-dos.' : filter === 'done' ? 'Deine abgeschlossenen To-dos erscheinen hier.' : 'Lege ein To-do an oder wechsle den Filter.';
}
document.querySelectorAll('[data-filter]').forEach(button => button.addEventListener('click', () => {
  filter = button.dataset.filter;
  document.querySelectorAll('[data-filter]').forEach(tab => tab.classList.toggle('selected', tab === button));
  filterTasks();
}));
document.querySelector('#search').addEventListener('input', filterTasks);
function updateTime() {
  const now = Date.now() / 1000;
  document.querySelectorAll('.task-card').forEach(card => {
    const start = Number(card.dataset.start), due = Number(card.dataset.due), remaining = due - now;
    const percent = Math.max(0, Math.min(100, remaining / (due - start) * 100));
    const done = card.dataset.done === '1';
    const track = card.querySelector('.progress-track');
    track.setAttribute('aria-valuenow', Math.round(percent));
    card.querySelector('.progress-fill').style.width = `${percent}%`;
    card.querySelector('.percent').textContent = done ? 'Erledigt' : `${Math.round(percent)} % übrig`;
    const minutes = Math.max(1, Math.ceil(Math.abs(remaining) / 60));
    const duration = minutes >= 1440 ? `${Math.floor(minutes / 1440)} T ${Math.floor(minutes % 1440 / 60)} Std` : minutes >= 60 ? `${Math.floor(minutes / 60)} Std ${minutes % 60} Min` : `${minutes} Min`;
    card.querySelector('.time-left').textContent = done ? '✓ Abgeschlossen' : now < start ? 'Geplant · startet am ' + new Date(start * 1000).toLocaleDateString('de-AT') : remaining <= 0 ? `${duration} überfällig` : `◷ Noch ${duration}`;
    card.classList.toggle('overdue', !done && remaining <= 0);
  });
}
filterTasks(); updateTime(); setInterval(updateTime, 30000);
