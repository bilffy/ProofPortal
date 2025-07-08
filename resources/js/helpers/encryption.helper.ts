export const encryptData = (rawData: string): string => {
    return toHex(rawData);
}

export const decryptData = (encryptedData: string): string => {
    return fromHex(encryptedData);
}

export const encryptObjectValues = (obj: Record<string, string>): Record<string, string> => {
    const encryptedObj: Record<string, string> = {};
    for (const key in obj) {
        if (obj.hasOwnProperty(key)) {
            encryptedObj[key] = encryptData(obj[key]);
        }
    }
    return encryptedObj;
}

function toHex(data: string): string {
  return Array.from(data)
    .map(ch => ch.charCodeAt(0).toString(16).padStart(2, '0'))
    .join('');
}

function fromHex(hex: string): string {
  const bytes = hex.match(/.{1,2}/g);
  if (!bytes) {
    return '';
  }

  return bytes
    .map(byte => String.fromCharCode(parseInt(byte, 16)))
    .join('');
}
