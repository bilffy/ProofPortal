import JsAesPhp from './js-aes-php';

export const encryptData = async (rawData: string, nonce: string): Promise<string> => {
    return await JsAesPhp.encrypt(rawData, nonce)
}

export const decryptData = async (encryptedData: string, nonce: string): Promise<string> => {
    return await JsAesPhp.decrypt(encryptedData, nonce);
}

export const encryptObjectValues = async (obj: Record<string, string>, nonce: string): Promise<Record<string, string>> => {
    const encryptedObj: Record<string, string> = {};
    for (const key in obj) {
        if (obj.hasOwnProperty(key)) {
            encryptedObj[key] = await encryptData(obj[key], nonce);
        }
    }
    return encryptedObj;
}
