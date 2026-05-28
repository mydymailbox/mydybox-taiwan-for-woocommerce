#!/usr/bin/env python3
# Build .mo from .po correctly
import struct, os, sys

BASE = os.path.dirname(os.path.abspath(__file__))
PO = os.path.join(BASE, 'taiwan-store-core-zh_TW.po')
MO = os.path.join(BASE, 'taiwan-store-core-zh_TW.mo')

# Parse .po
def parse_po(path):
    entries = {}
    msgid = msgstr = None
    in_id = in_str = False

    def unescape(s):
        return (s.replace('\\n', '\n')
                 .replace('\\t', '\t')
                 .replace('\\"', '"')
                 .replace('\\\\', '\\'))

    with open(path, 'r', encoding='utf-8') as f:
        for line in f:
            line = line.rstrip('\n')
            if line.startswith('msgid "'):
                if msgid is not None and msgstr is not None and msgid != '':
                    entries[msgid] = msgstr
                msgid = unescape(line[7:-1])
                msgstr = None
                in_id = True; in_str = False
            elif line.startswith('msgstr "'):
                msgstr = unescape(line[8:-1])
                in_id = False; in_str = True
            elif line.startswith('"'):
                val = unescape(line[1:-1])
                if in_id:    msgid  += val
                elif in_str: msgstr += val
            elif line.strip() == '':
                if msgid and msgstr is not None and msgid != '':
                    entries[msgid] = msgstr
                msgid = msgstr = None
                in_id = in_str = False

    if msgid and msgstr is not None and msgid != '':
        entries[msgid] = msgstr
    return entries

entries = parse_po(PO)
print(f'Parsed {len(entries)} entries')

for k, v in entries.items():
    if 'Taiwan Store' in k:
        print(f'  {repr(k)} -> {repr(v)}')

# Write .mo
# Format: header(28) + orig_table(N*8) + trans_table(N*8) + string_data
keys = sorted(entries.keys())
N = len(keys)

HEADER_SIZE = 28
O_OFF = HEADER_SIZE           # orig  strings table offset
T_OFF = HEADER_SIZE + N * 8  # trans strings table offset
data_start = HEADER_SIZE + N * 16

# Build string blobs and offset tables
orig_blob  = b''
trans_blob = b''
id_offsets  = []
str_offsets = []

cur = data_start
for k in keys:
    kb = k.encode('utf-8')
    id_offsets.append((len(kb), cur))
    orig_blob += kb + b'\x00'
    cur += len(kb) + 1

for k in keys:
    vb = entries[k].encode('utf-8')
    str_offsets.append((len(vb), cur))
    trans_blob += vb + b'\x00'
    cur += len(vb) + 1

mo = b''
mo += struct.pack('<I', 0x950412de)  # magic (little-endian)
mo += struct.pack('<I', 0)           # file format revision
mo += struct.pack('<I', N)           # number of strings
mo += struct.pack('<I', O_OFF)       # offset of orig  table
mo += struct.pack('<I', T_OFF)       # offset of trans table
mo += struct.pack('<II', 0, 0)       # hash table size + offset (unused)

for length, offset in id_offsets:
    mo += struct.pack('<II', length, offset)
for length, offset in str_offsets:
    mo += struct.pack('<II', length, offset)

mo += orig_blob + trans_blob

with open(MO, 'wb') as f:
    f.write(mo)

print(f'Written {N} entries -> {MO}')

# Verify
with open(MO, 'rb') as f:
    data = f.read()
_, N2, O2, T2 = struct.unpack('<4I', data[4:20])
print(f'Verify: {N2} entries in .mo')
for i in range(N2):
    olen, ooff = struct.unpack('<2I', data[O2+i*8:O2+i*8+8])
    tlen, toff = struct.unpack('<2I', data[T2+i*8:T2+i*8+8])
    kid = data[ooff:ooff+olen].decode('utf-8')
    ktr = data[toff:toff+tlen].decode('utf-8')
    if 'Taiwan Store' in kid or kid in ('台灣', '超商取貨'):
        print(f'  OK: {repr(kid)} -> {repr(ktr)}')
