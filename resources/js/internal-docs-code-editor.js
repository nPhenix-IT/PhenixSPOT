import Sortable from 'sortablejs';
import * as monaco from 'monaco-editor';

window.Sortable = Sortable;

window.initInternalDocsMonaco = ({
  textarea,
  container,
  isDark = false,
  language = 'php',
} = {}) => {
  if (!textarea || !container || !window.monaco?.editor) {
    return null;
  }

  const editor = monaco.editor.create(container, {
    value: textarea.value || '',
    language,
    theme: isDark ? 'vs-dark' : 'vs',
    automaticLayout: true,
    fontSize: 13,
    minimap: { enabled: true },
    scrollBeyondLastLine: false,
    wordWrap: 'on',
  });

  editor.onDidChangeModelContent(() => {
    textarea.value = editor.getValue();
  });

  textarea.style.display = 'none';
  return editor;
};
