/**
 * Inline cohort management on Moodle user profiles.
 *
 * @module local_cohortbadges/profile_manager
 * @copyright 2026 MIA Academy
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
(function() {
    'use strict';

    /**
     * Send a sesskey-protected request to the local membership endpoint.
     *
     * @param {HTMLElement} root Manager element.
     * @param {string} action Search, add or remove.
     * @param {Object} extras Action-specific parameters.
     * @returns {Promise<Object>} JSON result.
     */
    async function request(root, action, extras) {
        const body = new URLSearchParams({
            action: action,
            userid: root.dataset.userid,
            sesskey: root.dataset.sesskey,
            ...extras
        });
        const response = await fetch(root.dataset.endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
            body: body.toString()
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok || data.error) {
            throw new Error(data.error || root.dataset.error);
        }
        return data;
    }

    /**
     * Create a button using safe text nodes rather than HTML strings.
     *
     * @param {string} text Button label.
     * @param {string} className CSS classes.
     * @param {string} action Delegated action.
     * @returns {HTMLButtonElement}
     */
    function button(text, className, action) {
        const element = document.createElement('button');
        element.type = 'button';
        element.className = className;
        element.dataset.action = action;
        element.textContent = text;
        return element;
    }

    /**
     * Prepare a manager and all of its delegated event handlers.
     *
     * @param {HTMLElement} root Inline manager element.
     */
    function initialise(root) {
        const panel = root.querySelector('[data-role="panel"]');
        const toggle = root.querySelector('[data-role="toggle"]');
        const search = root.querySelector('[data-role="search"]');
        const results = root.querySelector('[data-role="results"]');
        const more = root.querySelector('[data-role="more"]');
        const feedback = root.querySelector('[data-role="feedback"]');
        const memberships = root.querySelector('[data-role="memberships"]');

        let page = 0;
        let query = '';
        let sequence = 0;
        let debounce;
        let busy = false;

        function message(text, error) {
            feedback.textContent = text || '';
            feedback.classList.toggle('text-danger', Boolean(error));
        }

        function setResultsOpen(open) {
            results.hidden = !open;
            search.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        function renderResults(items, append) {
            if (!append) {
                results.replaceChildren();
            }
            items.forEach((item) => {
                const option = button(root.dataset.add, 'local-cohortbadges-picker-option', 'add');
                option.setAttribute('role', 'option');
                option.dataset.cohortid = String(item.id);

                const text = document.createElement('span');
                text.className = 'local-cohortbadges-picker-option-text';
                text.textContent = item.name;

                if (item.idnumber) {
                    const idnumber = document.createElement('small');
                    idnumber.className = 'text-muted';
                    idnumber.textContent = item.idnumber;
                    text.appendChild(idnumber);
                }
                const action = document.createElement('span');
                action.className = 'local-cohortbadges-picker-add';
                action.textContent = '+';
                option.replaceChildren(text, action);
                option.setAttribute('aria-label', root.dataset.add + ': ' + item.name);
                results.appendChild(option);
            });

            if (results.children.length === 0) {
                const empty = document.createElement('p');
                empty.className = 'local-cohortbadges-picker-empty';
                empty.textContent = root.dataset.noresults;
                results.appendChild(empty);
            }
            setResultsOpen(true);
        }

        async function loadResults(append) {
            const requestedQuery = search.value.trim();
            if (!append || requestedQuery !== query) {
                page = 0;
                query = requestedQuery;
                append = false;
            }
            const requestedPage = page;
            const requestId = ++sequence;
            message(root.dataset.loading, false);
            more.hidden = true;
            try {
                const data = await request(root, 'search', {
                    search: query,
                    page: String(requestedPage)
                });
                if (requestId !== sequence || panel.hidden) {
                    return;
                }
                renderResults(data.items, append);
                more.hidden = !data.hasmore;
                message('', false);
            } catch (error) {
                if (requestId !== sequence) {
                    return;
                }
                setResultsOpen(false);
                message(error.message || root.dataset.error, true);
            }
        }

        async function updateMembership(action, cohortid, confirmed) {
            if (busy) {
                return;
            }
            busy = true;
            sequence++;
            message(root.dataset.loading, false);
            try {
                const data = await request(root, action, {
                    cohortid: String(cohortid),
                    confirm: confirmed ? '1' : '0'
                });
                memberships.innerHTML = data.html;
                message(action === 'add' ? root.dataset.added : root.dataset.removed, false);
                if (!panel.hidden) {
                    await loadResults(false);
                    message(action === 'add' ? root.dataset.added : root.dataset.removed, false);
                }
            } catch (error) {
                message(error.message || root.dataset.error, true);
            } finally {
                busy = false;
            }
        }

        function showRemoveConfirmation(trigger) {
            root.querySelectorAll('[data-role="remove-confirmation"]').forEach((item) => item.remove());
            const holder = document.createElement('span');
            holder.className = 'local-cohortbadges-remove-confirmation';
            holder.dataset.role = 'remove-confirmation';

            const prompt = document.createElement('span');
            prompt.textContent = root.dataset.confirmremove.replace('{cohort}', trigger.dataset.cohortname);
            const confirm = button(root.dataset.confirm, 'btn btn-danger btn-sm', 'confirm-remove');
            confirm.dataset.cohortid = trigger.dataset.cohortid;
            const cancel = button(root.dataset.cancel, 'btn btn-light btn-sm', 'cancel-remove');
            holder.append(prompt, confirm, cancel);
            trigger.closest('.local-cohortbadges-profile-cohort').appendChild(holder);
            confirm.focus();
        }

        toggle.addEventListener('click', () => {
            panel.hidden = !panel.hidden;
            toggle.setAttribute('aria-expanded', panel.hidden ? 'false' : 'true');
            if (!panel.hidden) {
                search.focus();
            } else {
                sequence++;
                setResultsOpen(false);
            }
        });

        search.addEventListener('focus', () => {
            if (!panel.hidden) {
                loadResults(false);
            }
        });
        search.addEventListener('input', () => {
            clearTimeout(debounce);
            debounce = setTimeout(() => loadResults(false), 220);
        });
        search.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                setResultsOpen(false);
                return;
            }
            if (event.key === 'ArrowDown' && !results.hidden) {
                const first = results.querySelector('button');
                if (first) {
                    event.preventDefault();
                    first.focus();
                }
            }
        });

        more.addEventListener('click', () => {
            page++;
            loadResults(true);
        });

        root.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-action]');
            if (!trigger || !root.contains(trigger)) {
                return;
            }
            switch (trigger.dataset.action) {
                case 'add':
                    updateMembership('add', trigger.dataset.cohortid, false);
                    break;
                case 'remove':
                    showRemoveConfirmation(trigger);
                    break;
                case 'confirm-remove':
                    updateMembership('remove', trigger.dataset.cohortid, true);
                    break;
                case 'cancel-remove':
                    trigger.closest('[data-role="remove-confirmation"]').remove();
                    break;
            }
        });
    }

    const start = () => document.querySelectorAll('[data-cohortbadges-manager]').forEach(initialise);
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
}());
