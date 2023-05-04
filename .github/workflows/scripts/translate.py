import requests
import json
import os

def main():
    with open('resources/lang/en.json', 'r') as f:
        en_data = json.load(f)

    headers = {'Authorization': f'Bearer {os.environ["OPENAI_API_KEY"]}'}
    url = 'https://api.openai.com/v1/translations'
    languages = {'es': 'Spanish', 'de': 'German', 'fr': 'French', 'ja': 'Japanese'}

    for lang, lang_name in languages.items():
        file_path = f'resources/lang/{lang}.json'
        translated_data = json.load(open(file_path, 'r')) if os.path.exists(file_path) else {}
        changed = False

        for key, value in en_data.items():
            if key not in translated_data or translated_data[key] != value:
                data = {'source': 'en', 'target': lang, 'text': value}
                response = requests.post(url, headers=headers, json=data)
                translated_data[key] = response.json()['choices'][0]['text'].strip()
                changed = True

        if changed:
            with open(file_path, 'w') as f:
                json.dump(translated_data, f, ensure_ascii=False, indent=2)

if __name__ == '__main__':
    main()
