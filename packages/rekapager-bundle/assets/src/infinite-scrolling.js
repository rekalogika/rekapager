/*
 * This file is part of rekalogika/rekapager package.
 *
 * (c) Priyadi Iman Nurcahyo <https://rekalogika.dev>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

'use strict'

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        breakpoint: { type: String, default: '768px' },
        pagination: { type: String, default: '.pagination' },
        controllerName: { type: String, default: 'rekalogika--rekapager-bundle--infinite-scrolling' }
    }

    connect() {
        let mediaQuery = '(min-width: ' + this.breakpointValue + ')'

        if (window.matchMedia(mediaQuery).matches) {
            return;
        }

        let href = this.getNextHref(document)

        if (href) {
            this.href = href
            window.addEventListener('scroll', this.onScroll.bind(this))
        }
    }

    getNextHref(node) {
        let paginations = node.querySelectorAll(this.paginationValue)

        if (paginations.length == 0) {
            console.log('Error: no pagination element found')
            return null
        } else if (paginations.length > 1) {
            console.log('Error: more than one pagination found')
            return null
        }

        let pagination = paginations[0]
        pagination.remove()

        let next = pagination.querySelector('[rel="next"]')

        if (!next) {
            console.log('Error: no next link found')
            return null
        }

        let href = next.getAttribute('href')

        if (!href || href == '#') {
            return null
        }

        return href
    }

    onScroll() {
        if (window.scrollY + window.innerHeight >= document.documentElement.scrollHeight - 1) {
            this.goFetch()
        }
    }

    goFetch() {
        // disable scroll event to prevent multiple fetch
        window.removeEventListener('scroll', this.onScroll)
        let href = this.href

        if (!href) {
            return
        }

        this.href = null

        fetch(href)
            .then(response => response.text())
            .then(html => {
                // append new elements

                let fragment = document.createRange().createContextualFragment(html)
                let newElements = fragment
                    .querySelector('[data-controller~="'+ this.controllerNameValue +'"]')
                    .children
                document.querySelector('[data-controller~="' + this.controllerNameValue + '"]')
                    .append(...newElements)

                // find next link

                if (href = this.getNextHref(fragment)) {
                    this.href = href
                    window.addEventListener('scroll', this.onScroll.bind(this))
                }
            })
    }
}

