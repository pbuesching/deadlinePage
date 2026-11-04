"""Integrationstest gegen den laufenden PHP/MySQL-Stack; räumt Testdaten auf."""
import http.cookiejar
import os
import re
import urllib.error
import urllib.parse
import urllib.request
import uuid

base = os.environ.get('TEST_BASE_URL', 'http://127.0.0.1:8080/').rstrip('/') + '/'
client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

def get():
    return client.open(base).read().decode()

def post(values, expected=200):
    try:
        response = client.open(base, urllib.parse.urlencode(values).encode())
        assert response.status == expected, response.status
        return response.read().decode()
    except urllib.error.HTTPError as error:
        assert error.code == expected, error.code
        return error.read().decode()

html = get()
csrf = re.search(r'name="csrf" value="([a-f0-9]+)"', html)[1]
title = 'Integration-' + uuid.uuid4().hex
values = dict(action='save', csrf=csrf, title=title, description='<script>alert(1)</script>', color='#48a88b', starts_at='2026-01-01T10:00', due_at='2027-01-01T10:00', main_at='2027-01-02T10:00')
id_ = None
try:
    html = post(values)
    assert title in html and '&lt;script&gt;alert(1)&lt;/script&gt;' in html
    assert 'style="--task-color:#48a88b"' in html
    assert '02.01.2027 · 10:00' in html
    # Extract the test card's ID without depending on other stored tasks.
    card = next(card for card in html.split('<article ')[1:] if f'data-title="{title}"' in card)
    id_ = re.search(r'name="id" value="(\d+)"', card)[1]
    html = post({**values, 'id': id_, 'title': title + '-edited'})
    assert title + '-edited' in html
    post({**values, 'id': id_, 'due_at': '2025-01-01T10:00'}, 422)
    post({**values, 'id': id_, 'main_at': '2026-01-01T10:00'}, 422)
    post(dict(action='delete', id=id_, csrf='invalid'), 403)
    assert title + '-edited' in get()
    html = post(dict(action='toggle', id=id_, csrf=csrf))
    card = next(card for card in html.split('<article ')[1:] if f'data-title="{title}-edited"' in card)
    assert 'data-done="1"' in card
    html = post(dict(action='toggle', id=id_, csrf=csrf))
    card = next(card for card in html.split('<article ')[1:] if f'data-title="{title}-edited"' in card)
    assert 'data-done="0"' in card
finally:
    if id_:
        post(dict(action='delete', id=id_, csrf=csrf))
assert title not in get()
print('PASS: create, read, update, complete, reopen, delete, colors, dates, validation, CSRF, HTML escaping')
