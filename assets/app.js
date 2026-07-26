(function () {
  'use strict';

  function qs(sel, root) {
    return (root || document).querySelector(sel);
  }

  function qsa(sel, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(sel));
  }

  // List: expand subagents
  qsa('[data-expand]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var targetId = btn.getAttribute('data-expand');
      var group = document.getElementById(targetId);
      if (!group) return;
      var open = group.classList.toggle('is-open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      btn.textContent = open ? '▼' : '▶';
    });
  });

  // List: client-side filters
  var searchInput = qs('#filter-search');
  var statusSelect = qs('#filter-status');
  var starredOnly = qs('#filter-starred');
  var hideArchived = qs('#filter-hide-archived');

  function rowMatches(row) {
    var q = (searchInput && searchInput.value || '').trim().toLowerCase();
    var status = statusSelect ? statusSelect.value : '';
    var starred = starredOnly && starredOnly.checked;
    var hideArch = hideArchived && hideArchived.checked;

    var trackStatus = row.getAttribute('data-track-status') || 'open';
    // Hide archived only when browsing "all" (or open/done), not when filter is explicitly Archived
    if (hideArch && status !== 'archived' && trackStatus === 'archived') {
      return false;
    }
    if (status && trackStatus !== status) return false;
    if (starred && row.getAttribute('data-starred') !== '1') return false;

    if (q) {
      var hay = (row.getAttribute('data-search') || '').toLowerCase();
      if (hay.indexOf(q) === -1) return false;
    }
    return true;
  }

  function applyListFilters() {
    var visible = 0;
    qsa('tr[data-chat-row]').forEach(function (row) {
      var show = rowMatches(row);
      row.classList.toggle('hidden', !show);
      if (show) visible++;
    });
    var counter = qs('#visible-count');
    if (counter) counter.textContent = String(visible);
  }

  [searchInput, statusSelect, starredOnly, hideArchived].forEach(function (el) {
    if (!el) return;
    el.addEventListener('input', applyListFilters);
    el.addEventListener('change', applyListFilters);
  });
  if (statusSelect) {
    statusSelect.addEventListener('change', function () {
      if (statusSelect.value === 'archived' && hideArchived) {
        hideArchived.checked = false;
      }
    });
  }
  applyListFilters();

  // Plans list filter
  var planSearch = qs('#filter-plan-search');
  function applyPlanFilters() {
    var q = (planSearch && planSearch.value || '').trim().toLowerCase();
    var visible = 0;
    qsa('tr[data-plan-row]').forEach(function (row) {
      var show = true;
      if (q) {
        var hay = (row.getAttribute('data-search') || '').toLowerCase();
        show = hay.indexOf(q) !== -1;
      }
      row.classList.toggle('hidden', !show);
      if (show) visible++;
    });
    var counter = qs('#plan-visible-count');
    if (counter) counter.textContent = String(visible);
  }
  if (planSearch) {
    planSearch.addEventListener('input', applyPlanFilters);
    applyPlanFilters();
  }

  var ruleSearch = qs('#filter-rule-search');
  function applyRuleFilters() {
    var q = (ruleSearch && ruleSearch.value || '').trim().toLowerCase();
    var visible = 0;
    qsa('tr[data-rule-row]').forEach(function (row) {
      var show = true;
      if (q) {
        var hay = (row.getAttribute('data-search') || '').toLowerCase();
        show = hay.indexOf(q) !== -1;
      }
      row.classList.toggle('hidden', !show);
      if (show) visible++;
    });
    var counter = qs('#rule-visible-count');
    var totalEl = qs('#rule-total-count');
    if (counter) counter.textContent = String(visible);
    if (totalEl) totalEl.textContent = String(qsa('tr[data-rule-row]').length);
  }
  if (ruleSearch) {
    ruleSearch.addEventListener('input', applyRuleFilters);
    applyRuleFilters();
  }

  document.addEventListener('click', function (e) {
    var openBtn = e.target.closest('[data-open-location]');
    if (openBtn) {
      var kind = openBtn.getAttribute('data-open-kind');
      var payload = { kind: kind };
      if (kind === 'plan') {
        payload.f = openBtn.getAttribute('data-open-f');
      } else if (kind === 'rule') {
        payload.f = openBtn.getAttribute('data-open-f');
      } else if (kind === 'chat') {
        payload.id = openBtn.getAttribute('data-open-id');
        payload.sub = openBtn.getAttribute('data-open-sub') || '';
      }
      var actions = openBtn.closest('.row-actions');
      var pathBtn = actions && actions.querySelector('[data-copy-path]');
      var pathToCopy = pathBtn ? pathBtn.getAttribute('data-copy-path') : '';
      if (pathToCopy && navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(pathToCopy);
      }
      var openLabel = openBtn.textContent;
      openBtn.textContent = 'Opening…';
      openBtn.disabled = true;
      fetch('api/open_location.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          openBtn.disabled = false;
          openBtn.textContent = openLabel;
          if (!data || !data.ok) {
            var msg = (data && data.error) ? data.error : 'Could not open folder.';
            if (pathToCopy) {
              msg += '\n\nThe full path was copied to your clipboard — paste it into Explorer’s address bar (Win+E).';
            }
            if (data && data.hints && data.hints.length) {
              msg += '\n\n' + data.hints.join('\n');
            }
            window.alert(msg);
            return;
          }
          if (pathToCopy) {
            openBtn.textContent = 'Path copied';
            window.setTimeout(function () {
              openBtn.textContent = openLabel;
            }, 2500);
            openBtn.setAttribute(
              'title',
              (data.hint || '') + ' Win+E, paste path if Explorer did not open.'
            );
          }
        })
        .catch(function () {
          openBtn.disabled = false;
          openBtn.textContent = openLabel;
          var msg = 'Could not contact the server.';
          if (pathToCopy) {
            msg += ' The path was copied — paste it into Explorer (Win+E).';
          }
          window.alert(msg);
        });
      return;
    }

    var copyBtn = e.target.closest('[data-copy-path]');
    if (copyBtn) {
      var path = copyBtn.getAttribute('data-copy-path');
      if (!path) return;
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(path).then(function () {
          copyBtn.textContent = 'Copied';
          window.setTimeout(function () { copyBtn.textContent = 'Copy path'; }, 1200);
        });
      }
      return;
    }

    var delBtn = e.target.closest('[data-plan-delete]');
    if (delBtn) {
      var fname = delBtn.getAttribute('data-plan-delete');
      if (!fname) return;
      if (!window.confirm('Delete plan file “' + fname + '”? This cannot be undone.')) {
        return;
      }
      delBtn.disabled = true;
      fetch('api/plan_delete.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ f: fname })
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (!data || !data.ok) {
            delBtn.disabled = false;
            window.alert((data && data.error) ? data.error : 'Delete failed.');
            return;
          }
          var redirect = delBtn.getAttribute('data-redirect');
          if (redirect) {
            window.location.href = redirect;
            return;
          }
          var row = delBtn.closest('tr[data-plan-row]');
          if (row) {
            row.parentNode.removeChild(row);
          }
          applyPlanFilters();
        })
        .catch(function () {
          delBtn.disabled = false;
          window.alert('Delete failed (network error).');
        });
      return;
    }

    var ruleDelBtn = e.target.closest('[data-rule-delete]');
    if (ruleDelBtn) {
      var ruleFname = ruleDelBtn.getAttribute('data-rule-delete');
      var ruleRedirect = ruleDelBtn.getAttribute('data-redirect') || '';
      if (!ruleFname) return;
      if (!window.confirm('Delete rule file “' + ruleFname + '” from disk? This cannot be undone.')) {
        return;
      }
      ruleDelBtn.disabled = true;
      fetch('api/rule_delete.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ f: ruleFname })
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (!data || !data.ok) {
            window.alert((data && data.error) ? data.error : 'Delete failed.');
            ruleDelBtn.disabled = false;
            return;
          }
          if (ruleRedirect) {
            window.location.href = ruleRedirect;
            return;
          }
          var row = ruleDelBtn.closest('tr[data-rule-row]');
          if (row) {
            row.remove();
            applyRuleFilters();
          }
        })
        .catch(function () {
          window.alert('Delete failed (network error).');
          ruleDelBtn.disabled = false;
        });
      return;
    }

    var chatDelBtn = e.target.closest('[data-chat-delete]');
    if (chatDelBtn) {
      var chatId = chatDelBtn.getAttribute('data-id');
      var chatSub = chatDelBtn.getAttribute('data-sub') || '';
      if (!chatId) return;
      var label = chatSub ? chatSub : chatId;
      if (!window.confirm('Delete transcript file for “' + label + '”? This cannot be undone.')) {
        return;
      }
      chatDelBtn.disabled = true;
      fetch('api/chat_delete.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: chatId, sub: chatSub })
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (!data || !data.ok) {
            chatDelBtn.disabled = false;
            window.alert((data && data.error) ? data.error : 'Delete failed.');
            return;
          }
          var redirectChat = chatDelBtn.getAttribute('data-redirect');
          if (redirectChat) {
            window.location.href = redirectChat;
            return;
          }
          var row = chatDelBtn.closest('tr[data-chat-row]');
          if (!row) return;
          if (row.getAttribute('data-chat-parent') === '1') {
            var tbody = row.closest('tbody');
            if (tbody) {
              var next = tbody.nextElementSibling;
              if (next && next.classList.contains('subagent-rows')) {
                next.parentNode.removeChild(next);
              }
              tbody.parentNode.removeChild(tbody);
            }
          } else {
            row.parentNode.removeChild(row);
          }
          if (typeof applyListFilters === 'function') {
            applyListFilters();
          }
        })
        .catch(function () {
          chatDelBtn.disabled = false;
          window.alert('Delete failed (network error).');
        });
      return;
    }
  });

  function postTracking(payload) {
    return fetch('api/tracking.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    }).then(function (r) { return r.json(); });
  }

  // Status select on list (quick save)
  qsa('[data-row-status]').forEach(function (sel) {
    var previous = sel.value;
    sel.addEventListener('change', function () {
      var next = sel.value;
      sel.classList.add('is-saving');
      sel.classList.remove('is-error');
      postTracking({
        id: sel.getAttribute('data-id'),
        sub: sel.getAttribute('data-sub') || '',
        starred: sel.getAttribute('data-starred') === '1',
        status: next,
        notes: sel.getAttribute('data-notes') || '',
        title_override: sel.getAttribute('data-title-override') || ''
      })
        .then(function (data) {
          sel.classList.remove('is-saving');
          if (!data.ok) {
            sel.classList.add('is-error');
            sel.value = previous;
            return;
          }
          previous = next;
          var row = sel.closest('tr');
          if (row) {
            row.setAttribute('data-track-status', next);
          }
          var starBtn = row && row.querySelector('.star-btn[data-quick-star]');
          if (starBtn) {
            starBtn.setAttribute('data-status', next);
          }
          applyListFilters();
        })
        .catch(function () {
          sel.classList.remove('is-saving');
          sel.classList.add('is-error');
          sel.value = previous;
        });
    });
  });

  // Star toggle on list (quick save)
  qsa('.star-btn[data-quick-star]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      var id = btn.getAttribute('data-id');
      var sub = btn.getAttribute('data-sub') || '';
      var starred = btn.classList.contains('is-starred') ? false : true;
      postTracking({
        id: id,
        sub: sub,
        starred: starred,
        status: btn.getAttribute('data-status') || 'open',
        notes: btn.getAttribute('data-notes') || '',
        title_override: btn.getAttribute('data-title-override') || ''
      })
        .then(function (data) {
          if (!data || !data.ok) {
            btn.classList.add('is-error');
            window.setTimeout(function () { btn.classList.remove('is-error'); }, 1200);
            return;
          }
          btn.classList.remove('is-error');
          btn.classList.toggle('is-starred', starred);
          btn.textContent = starred ? '★' : '☆';
          btn.setAttribute('aria-pressed', starred ? 'true' : 'false');
          var row = btn.closest('tr');
          if (row) {
            row.setAttribute('data-starred', starred ? '1' : '0');
          }
          var statusSel = row && row.querySelector('[data-row-status]');
          if (statusSel) {
            statusSel.setAttribute('data-starred', starred ? '1' : '0');
          }
          applyListFilters();
        })
        .catch(function () {
          btn.classList.add('is-error');
          window.setTimeout(function () { btn.classList.remove('is-error'); }, 1200);
        });
    });
  });

  // Chat detail: tracking form
  var form = qs('#tracking-form');
  if (form) {
    var statusEl = qs('#save-status');
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var payload = {
        id: form.getAttribute('data-id'),
        sub: form.getAttribute('data-sub') || '',
        starred: qs('#track-starred', form).checked,
        status: qs('#track-status', form).value,
        notes: qs('#track-notes', form).value,
        title_override: qs('#track-title', form).value
      };
      if (statusEl) {
        statusEl.textContent = 'Saving…';
        statusEl.className = 'save-status';
      }
      fetch('api/tracking.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (statusEl) {
            if (data.ok) {
              statusEl.textContent = 'Saved.';
              statusEl.className = 'save-status is-ok';
            } else {
              statusEl.textContent = data.error || 'Save failed.';
              statusEl.className = 'save-status is-err';
            }
          }
        })
        .catch(function () {
          if (statusEl) {
            statusEl.textContent = 'Network error.';
            statusEl.className = 'save-status is-err';
          }
        });
    });
  }

  function sortValueFromRow(row, key) {
    var raw = row.getAttribute('data-sort-' + key);
    if (raw === null || raw === '') {
      return '';
    }
    if (/^-?\d+$/.test(raw)) {
      return parseInt(raw, 10);
    }
    if (/^-?\d+\.\d+$/.test(raw)) {
      return parseFloat(raw);
    }

    return String(raw).toLowerCase();
  }

  function compareSortValues(a, b, dir) {
    var mul = dir === 'asc' ? 1 : -1;
    if (typeof a === 'number' && typeof b === 'number') {
      return (a - b) * mul;
    }
    var as = String(a);
    var bs = String(b);
    if (as < bs) return -1 * mul;
    if (as > bs) return 1 * mul;

    return 0;
  }

  function initSortableTable(table) {
    var mode = table.getAttribute('data-sortable-table');
    var currentKey = table.getAttribute('data-default-sort') || '';
    var currentDir = table.getAttribute('data-default-dir') || 'asc';
    var headers = qsa('.th-sortable', table);

    function setHeaderState() {
      headers.forEach(function (th) {
        var key = th.getAttribute('data-sort-key');
        th.classList.remove('is-sorted-asc', 'is-sorted-desc');
        if (key === currentKey) {
          th.classList.add(currentDir === 'asc' ? 'is-sorted-asc' : 'is-sorted-desc');
          th.setAttribute('aria-sort', currentDir === 'asc' ? 'ascending' : 'descending');
        } else {
          th.setAttribute('aria-sort', 'none');
        }
      });
    }

    function defaultDirForKey(key) {
      if (key === 'activity' || key === 'modified' || key === 'size' || key === 'msgs' || key === 'todos') {
        return 'desc';
      }

      return 'asc';
    }

    function sortPlans() {
      var tbody = table.querySelector('tbody');
      if (!tbody) return;
      var rows = qsa('tr[data-plan-row]', tbody);
      rows.sort(function (ra, rb) {
        return compareSortValues(
          sortValueFromRow(ra, currentKey),
          sortValueFromRow(rb, currentKey),
          currentDir
        );
      });
      rows.forEach(function (r) {
        tbody.appendChild(r);
      });
    }

    function sortRules() {
      var tbody = table.querySelector('tbody');
      if (!tbody) return;
      var rows = qsa('tr[data-rule-row]', tbody);
      rows.sort(function (ra, rb) {
        return compareSortValues(
          sortValueFromRow(ra, currentKey),
          sortValueFromRow(rb, currentKey),
          currentDir
        );
      });
      rows.forEach(function (r) {
        tbody.appendChild(r);
      });
    }

    function sortChats() {
      var groups = [];
      var tbodies = qsa('tbody', table);
      var i;
      for (i = 0; i < tbodies.length; i++) {
        var tb = tbodies[i];
        if (!tb.classList.contains('chat-group')) {
          continue;
        }
        var row = tb.querySelector('tr[data-chat-parent]');
        if (!row) {
          continue;
        }
        var sub = null;
        if (i + 1 < tbodies.length && tbodies[i + 1].classList.contains('subagent-rows')) {
          sub = tbodies[i + 1];
        }
        groups.push({ main: tb, sub: sub, row: row });
      }
      groups.sort(function (ga, gb) {
        return compareSortValues(
          sortValueFromRow(ga.row, currentKey),
          sortValueFromRow(gb.row, currentKey),
          currentDir
        );
      });
      groups.forEach(function (g) {
        table.appendChild(g.main);
        if (g.sub) {
          table.appendChild(g.sub);
        }
      });
    }

    function runSort() {
      if (mode === 'plans') {
        sortPlans();
      } else if (mode === 'rules') {
        sortRules();
      } else if (mode === 'chats') {
        sortChats();
      }
      setHeaderState();
    }

    headers.forEach(function (th) {
      function activate() {
        var key = th.getAttribute('data-sort-key');
        if (!key) return;
        if (currentKey === key) {
          currentDir = currentDir === 'asc' ? 'desc' : 'asc';
        } else {
          currentKey = key;
          currentDir = defaultDirForKey(key);
        }
        runSort();
      }
      th.addEventListener('click', activate);
      th.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          activate();
        }
      });
    });

    if (currentKey) {
      runSort();
    }
  }

  qsa('[data-sortable-table]').forEach(initSortableTable);
})();
