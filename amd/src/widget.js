// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Freshdesk Support Widget for Moodle.
 *
 * Renders a floating Help button that opens a modal containing:
 *  - Auto-suggested knowledge base articles on open (course name + activity type)
 *  - A search box that queries the Freshdesk knowledge base via the server proxy
 *  - Inline article viewer with option to open the full article in Freshdesk
 *  - A native contact form that submits tickets via Moodle AJAX (server-side proxy)
 *  - Optional screenshot attachment via file upload or clipboard paste
 *
 * Styles live in the plugin's styles.css; only the admin-configured colour is
 * set here, as the --local-freshdesk-colour CSS custom property.
 *
 * @module      local_freshdesk/widget
 * @copyright   2026 verzog
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Templates from 'core/templates';
import {getStrings} from 'core/str';

/** @type {Object} Plugin configuration passed from PHP via js_call_amd. */
let cfg = {};

/** @type {Object} Pre-translated UI strings keyed by identifier. */
const strs = {};

/** @type {string|null} Base64-encoded JPEG screenshot, or null when not set. */
let screenshotData = null;

/** Identifiers of the language strings the widget needs at runtime. */
const STRING_KEYS = [
    'articleloaderror', 'errormessage', 'errorsubject', 'initialprompt',
    'loadingarticle', 'loadingsuggestions', 'noarticles', 'nocontent',
    'openinfreshdesk', 'searching', 'searchunavailable', 'send', 'sending',
    'submittingas', 'suggestedheading', 'supportrequest', 'ticketsubmiterror',
];

/** Default icon when none is configured. */
const DEFAULT_ICON = '🎓';

/**
 * Shorthand for document.getElementById.
 *
 * @param {string} id Element id.
 * @returns {HTMLElement|null}
 */
const byId = (id) => document.getElementById(id);

/**
 * Shows or hides an element by id.
 *
 * @param {string} id Element id.
 * @param {string} display CSS display value; 'none' hides the element.
 */
const setDisplay = (id, display) => {
    const el = byId(id);
    if (el) {
        el.style.display = display;
    }
};

/**
 * Converts icon placeholders into an image (for URLs) or text (for Unicode).
 *
 * @param {HTMLElement} container Element to process icons in.
 */
const processIcons = (container) => {
    container.querySelectorAll('.fd-icon[data-icon]').forEach((el) => {
        const icon = el.getAttribute('data-icon');
        if (!icon) {
            return;
        }
        el.textContent = '';
        if (icon.match(/^https?:\/\//) || icon.includes('/')) {
            const img = document.createElement('img');
            img.src = icon;
            img.alt = '';
            el.appendChild(img);
        } else {
            el.textContent = icon;
        }
    });
};

/**
 * Builds the public Freshdesk URL of an article.
 *
 * @param {number} articleId Freshdesk article ID.
 * @returns {string}
 */
const articleUrl = (articleId) => cfg.portalUrl + '/support/solutions/articles/' + articleId;

/**
 * Searches the Freshdesk knowledge base through the server-side proxy.
 *
 * @param {string} term Search term.
 * @returns {Promise}
 */
const searchArticles = (term) => Ajax.call([{
    methodname: 'local_freshdesk_search_articles',
    args: {term: term},
}])[0];

/**
 * Fetches a single article through the server-side proxy.
 *
 * @param {number} articleId Freshdesk article ID.
 * @returns {Promise}
 */
const getArticle = (articleId) => Ajax.call([{
    methodname: 'local_freshdesk_get_article',
    args: {articleid: articleId},
}])[0];

/**
 * Sets the status line text and makes sure it is visible.
 *
 * @param {string} text Text to show.
 */
const setStatus = (text) => {
    const status = byId('fd-status');
    status.textContent = text;
    status.style.display = '';
};

/**
 * Displays a specific article in the viewer panel.
 *
 * @param {number} articleId Freshdesk article ID.
 */
const showArticle = (articleId) => {
    const articleContent = byId('fd-article-content');
    const fullUrl = articleUrl(articleId);

    setDisplay('fd-articles', 'none');
    setDisplay('fd-status', 'none');
    setDisplay('fd-contact-form', 'none');
    setDisplay('fd-article-view', 'flex');

    articleContent.textContent = '';
    const loading = document.createElement('p');
    loading.className = 'fd-article-loading';
    loading.textContent = strs.loadingarticle;
    articleContent.appendChild(loading);

    byId('fd-article-open-btn').onclick = () => {
        window.open(fullUrl, '_blank', 'noopener');
    };

    getArticle(articleId).then((data) => {
        if (!data || !data.id) {
            throw new Error('Not found');
        }
        if (data.description) {
            // The article HTML has already been purified server-side by format_text().
            articleContent.innerHTML = data.description;
        } else {
            articleContent.textContent = strs.nocontent;
        }
        return data;
    }).catch(() => {
        articleContent.textContent = '';
        const errp = document.createElement('p');
        errp.className = 'fd-article-error';
        errp.textContent = strs.articleloaderror + ' ';
        const link = document.createElement('a');
        link.href = fullUrl;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        link.textContent = strs.openinfreshdesk;
        errp.appendChild(link);
        articleContent.appendChild(errp);
    });
};

/**
 * Renders article results into the results panel.
 *
 * @param {Array} articles Articles returned by the search proxy.
 * @param {string} heading Status text shown above the results, or '' for none.
 */
const renderArticles = (articles, heading) => {
    const articlesDiv = byId('fd-articles');
    articlesDiv.textContent = '';

    if (!articles || articles.length === 0) {
        setStatus(strs.noarticles);
        return;
    }

    if (heading) {
        setStatus(heading);
    } else {
        setDisplay('fd-status', 'none');
    }

    articles.slice(0, 8).forEach((article) => {
        const item = document.createElement('div');
        item.className = 'fd-article-item';

        const title = document.createElement('a');
        title.className = 'fd-article-title';
        title.href = articleUrl(article.id);
        title.textContent = article.title || '';
        title.addEventListener('click', (e) => {
            e.preventDefault();
            showArticle(article.id);
        });

        // The proxy returns plain text, so textContent is all that is needed.
        const text = article.description_text || '';
        const desc = document.createElement('p');
        desc.className = 'fd-article-desc';
        desc.textContent = text.length > 120 ? text.substring(0, 120) + '...' : text;

        item.appendChild(title);
        item.appendChild(desc);
        articlesDiv.appendChild(item);
    });
};

/**
 * Extracts search terms from the current page context.
 *
 * @returns {Array}
 */
const getSearchTerms = () => {
    const terms = [];
    if (cfg.courseName) {
        terms.push(cfg.courseName);
    }
    if (cfg.currentUrl) {
        try {
            const parts = new URL(cfg.currentUrl).pathname.split('/').filter(Boolean);
            const modIdx = parts.indexOf('mod');
            if (modIdx !== -1 && parts[modIdx + 1] && terms.indexOf(parts[modIdx + 1]) === -1) {
                terms.push(parts[modIdx + 1]);
            }
        } catch (e) {
            // Ignore a malformed URL; the course name alone is still useful.
        }
    }
    return terms;
};

/**
 * Searches for several terms at once and merges the results without duplicates.
 *
 * @param {Array} terms Search terms.
 * @returns {Promise}
 */
const searchArticlesMulti = (terms) => {
    if (!terms || terms.length === 0) {
        return Promise.resolve([]);
    }
    return Promise.all(terms.map(searchArticles)).then((resultsArray) => {
        const merged = [];
        const seenIds = {};
        resultsArray.forEach((results) => {
            (results || []).forEach((article) => {
                if (!seenIds[article.id]) {
                    seenIds[article.id] = true;
                    merged.push(article);
                }
            });
        });
        return merged;
    });
};

/**
 * Loads suggested articles into the contact form based on page context.
 */
const loadSuggestedArticles = () => {
    const terms = getSearchTerms();
    if (!terms.length) {
        return;
    }

    const articlesDiv = byId('fd-suggest-articles');
    setDisplay('fd-suggest-section', 'block');
    articlesDiv.textContent = strs.loadingsuggestions;

    searchArticlesMulti(terms).then((results) => {
        if (!results || results.length === 0) {
            setDisplay('fd-suggest-section', 'none');
            return results;
        }
        articlesDiv.textContent = '';
        results.slice(0, 3).forEach((article) => {
            const link = document.createElement('a');
            link.className = 'fd-suggest-link';
            link.textContent = article.title;
            link.href = articleUrl(article.id);
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            articlesDiv.appendChild(link);
        });
        return results;
    }).catch(() => {
        setDisplay('fd-suggest-section', 'none');
    });
};

/**
 * Loads suggestions for the current page when the widget opens.
 */
const loadPageSuggestions = () => {
    const terms = getSearchTerms();
    if (!terms.length) {
        return;
    }

    setStatus(strs.loadingsuggestions);
    searchArticlesMulti(terms).then((results) => {
        if (!results || results.length === 0) {
            setStatus(strs.initialprompt);
        } else {
            renderArticles(results, strs.suggestedheading);
        }
        return results;
    }).catch(() => {
        setStatus(strs.initialprompt);
    });
};

/**
 * Removes any attached screenshot and hides its preview.
 */
const clearScreenshot = () => {
    screenshotData = null;
    byId('fd-screenshot-img').src = 'data:,';
    setDisplay('fd-screenshot-preview-wrap', 'none');
    byId('fd-screenshot-file').value = '';
};

/**
 * Hides the contact form validation / submission error.
 */
const clearContactError = () => {
    const errorEl = byId('fd-contact-error');
    errorEl.textContent = '';
    errorEl.style.display = 'none';
};

/**
 * Shows an error message on the contact form.
 *
 * @param {string} message Message to show.
 */
const showContactError = (message) => {
    const errorEl = byId('fd-contact-error');
    errorEl.textContent = message;
    errorEl.style.display = 'block';
};

/**
 * Displays the contact/ticket submission form.
 */
const showContactForm = () => {
    setDisplay('fd-search-panel', 'none');
    setDisplay('fd-contact-bar', 'none');
    setDisplay('fd-status', 'none');
    setDisplay('fd-articles', 'none');
    setDisplay('fd-article-view', 'none');

    const userInfoEl = byId('fd-contact-userinfo');
    userInfoEl.textContent = cfg.userName ? strs.submittingas + ' ' + cfg.userName : '';

    const subjectInput = byId('fd-ticket-subject');
    if (!subjectInput.value) {
        subjectInput.value = strs.supportrequest + (cfg.courseName ? ' - ' + cfg.courseName : '');
    }

    setDisplay('fd-contact-form', 'flex');
    byId('fd-ticket-message').focus();

    loadSuggestedArticles();
};

/**
 * Returns the contact form to a blank state, ready for a new ticket.
 */
const resetContactForm = () => {
    setDisplay('fd-contact-fields', '');
    setDisplay('fd-contact-success', 'none');
    byId('fd-ticket-subject').value = '';
    byId('fd-ticket-message').value = '';
    const submitBtn = byId('fd-contact-submit');
    submitBtn.disabled = false;
    submitBtn.textContent = strs.send;
    clearContactError();
    clearScreenshot();
};

/**
 * Processes a file or blob as a screenshot: scales it down and stores it as JPEG.
 *
 * @param {Blob} file Image file or clipboard blob.
 */
const processScreenshotFile = (file) => {
    const reader = new FileReader();
    reader.onload = (ev) => {
        const img = new Image();
        img.onload = () => {
            const canvas = document.createElement('canvas');
            const maxW = 1280;
            const scale = img.width > maxW ? maxW / img.width : 1;
            canvas.width = Math.round(img.width * scale);
            canvas.height = Math.round(img.height * scale);
            canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
            screenshotData = canvas.toDataURL('image/jpeg', 0.8).split(',')[1];
            byId('fd-screenshot-img').src = 'data:image/jpeg;base64,' + screenshotData;
            setDisplay('fd-screenshot-preview-wrap', 'block');
        };
        img.src = ev.target.result;
    };
    reader.readAsDataURL(file);
};

/**
 * Submits the ticket via AJAX.
 */
const submitTicket = () => {
    const subject = byId('fd-ticket-subject').value.trim();
    const message = byId('fd-ticket-message').value.trim();
    const submitBtn = byId('fd-contact-submit');

    if (!subject || !message) {
        showContactError(!subject ? strs.errorsubject : strs.errormessage);
        return;
    }

    clearContactError();
    submitBtn.disabled = true;
    submitBtn.textContent = strs.sending;

    Ajax.call([{
        methodname: 'local_freshdesk_submit_ticket',
        args: {
            subject: subject,
            message: message,
            currenturl: cfg.currentUrl || '',
            coursename: cfg.courseName || '',
            userrole: cfg.userRole || '',
            screenshot: screenshotData || '',
        },
    }])[0].then((result) => {
        if (result.success) {
            setDisplay('fd-contact-fields', 'none');
            setDisplay('fd-contact-success', 'block');
        }
        return result;
    }).catch(() => {
        showContactError(strs.ticketsubmiterror);
        submitBtn.disabled = false;
        submitBtn.textContent = strs.send;
    });
};

/**
 * Returns the modal to its default (search) view.
 */
const resetModal = () => {
    setDisplay('fd-articles', '');
    setStatus(strs.initialprompt);
    setDisplay('fd-article-view', 'none');
    setDisplay('fd-contact-form', 'none');
    setDisplay('fd-search-panel', '');
    setDisplay('fd-contact-bar', '');
    resetContactForm();
};

/**
 * Wires up DOM events.
 *
 * @param {HTMLElement} overlay The modal overlay element.
 */
const wireEvents = (overlay) => {
    const searchInput = byId('fd-search-input');
    const screenshotFile = byId('fd-screenshot-file');

    const closeModal = () => {
        overlay.style.display = 'none';
        resetModal();
    };

    byId('fd-help-btn').addEventListener('click', () => {
        overlay.style.display = 'block';
        searchInput.focus();
        loadPageSuggestions();
    });

    byId('fd-modal-close').addEventListener('click', closeModal);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && overlay.style.display === 'block') {
            closeModal();
        }
    });

    const runSearch = () => {
        const term = searchInput.value.trim();
        if (!term) {
            return;
        }
        setDisplay('fd-articles', '');
        setDisplay('fd-article-view', 'none');
        setStatus(strs.searching);
        searchArticles(term).then((results) => {
            renderArticles(results, '');
            return results;
        }).catch(() => {
            setStatus(strs.searchunavailable);
        });
    };

    byId('fd-search-btn').addEventListener('click', runSearch);
    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            runSearch();
        }
    });

    byId('fd-contact-btn').addEventListener('click', showContactForm);
    byId('fd-contact-submit').addEventListener('click', submitTicket);

    byId('fd-contact-back-btn').addEventListener('click', () => {
        setDisplay('fd-contact-form', 'none');
        setDisplay('fd-search-panel', '');
        setDisplay('fd-contact-bar', '');
        setDisplay('fd-status', '');
        setDisplay('fd-articles', '');
        resetContactForm();
    });

    byId('fd-article-back-btn').addEventListener('click', () => {
        setDisplay('fd-article-view', 'none');
        setDisplay('fd-articles', '');
        setDisplay('fd-status', '');
    });

    byId('fd-screenshot-attach').addEventListener('click', () => {
        screenshotFile.click();
    });
    screenshotFile.addEventListener('change', () => {
        if (screenshotFile.files && screenshotFile.files[0]) {
            processScreenshotFile(screenshotFile.files[0]);
        }
    });

    byId('fd-screenshot-clear').addEventListener('click', clearScreenshot);

    document.addEventListener('paste', (e) => {
        // Only capture pastes while the contact form is open, so pasting an image
        // elsewhere on the page (e.g. into a text editor) is never swallowed.
        const contactForm = byId('fd-contact-form');
        if (overlay.style.display !== 'block' || !contactForm || contactForm.style.display !== 'flex') {
            return;
        }
        const clipboard = e.clipboardData;
        if (!clipboard || !clipboard.items) {
            return;
        }
        const items = Array.from(clipboard.items);
        const image = items.find((item) => item.type.indexOf('image') !== -1);
        if (image) {
            processScreenshotFile(image.getAsFile());
        }
    });
};

/**
 * Loads the language strings used at runtime.
 *
 * @returns {Promise}
 */
const loadStrings = () => getStrings(STRING_KEYS.map((key) => ({key: key, component: 'local_freshdesk'})))
    .then((values) => {
        STRING_KEYS.forEach((key, idx) => {
            strs[key] = values[idx];
        });
        return strs;
    });

/**
 * Renders a template and appends it to the page body.
 *
 * @param {string} template Template name.
 * @param {Object} context Template context.
 * @returns {Promise} Resolves once the nodes are in the page.
 */
const appendTemplate = (template, context) => Templates.renderForPromise(template, context)
    .then(({html, js}) => {
        const nodes = Templates.appendNodeContents(document.body, html, js);
        nodes.forEach((node) => {
            if (node.nodeType === Node.ELEMENT_NODE) {
                processIcons(node);
            }
        });
        return nodes;
    });

/**
 * Initialise the widget.
 *
 * @param {Object} config Plugin configuration passed from PHP.
 * @returns {Promise}
 */
export const init = (config) => {
    cfg = config || {};

    if (!cfg.portalUrl || byId('fd-help-btn')) {
        return Promise.resolve();
    }

    if (cfg.widgetColor) {
        document.documentElement.style.setProperty('--local-freshdesk-colour', cfg.widgetColor);
    }
    const icon = cfg.widgetIcon || DEFAULT_ICON;

    if (!cfg.hasCapability) {
        // Pass-through mode: a plain link to the Freshdesk portal, no AJAX calls.
        return appendTemplate('local_freshdesk/help_button', {
            href: cfg.portalUrl + '/support/home',
            icon: icon,
        });
    }

    return loadStrings()
        .then(() => appendTemplate('local_freshdesk/modal', {userName: cfg.userName || '', icon: icon}))
        .then(() => appendTemplate('local_freshdesk/help_button', {icon: icon}))
        .then(() => {
            wireEvents(byId('fd-modal-overlay'));
            return true;
        });
};
