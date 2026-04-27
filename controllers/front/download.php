<?php

class Pixel_Product_FilesDownloadModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function initContent()
    {
        $idFile = (int)Tools::getValue('id_file');

        if (!$idFile) {
            $this->errors[] = $this->module->l('File ID is missing', 'download');
            $this->redirectWithNotifications($this->context->link->getPageLink('404'));
            return;
        }

        try {
            $productFile = Db::getInstance()->executeS("
                SELECT pf.file, pf.title, pl.name AS product_name
                FROM " . _DB_PREFIX_ . "product_file AS pf
                LEFT JOIN " . _DB_PREFIX_ . "product_lang AS pl
                    ON (pf.id_product = pl.id_product AND pl.id_lang = " . $this->context->language->id . ")
                WHERE pf.id = " . $idFile . " AND pf.id_lang = " . $this->context->language->id . " AND pf.id_shop = " . $this->context->shop->id
            );

            Db::getInstance()->execute("
                UPDATE " . _DB_PREFIX_ . "product_file 
                SET nb_download = nb_download + 1 
                WHERE id = " . $idFile . " AND id_lang = " . $this->context->language->id . " AND id_shop = " . $this->context->shop->id
            );

            $this->downloadFile($productFile[0]);
        } catch (\Exception $e) {
            PrestaShopLogger::addLog(
                'Error downloading file: ' . $e->getMessage(),
                3,
                null,
                'ProductFile',
                $idFile
            );
            Tools::redirect('index.php?controller=404');
        }
    }

    protected function getFileType(string $extension): string
    {
        foreach (Pixel_product_files::FILE_TYPES as $type => $extensions) {
            if (in_array(strtolower($extension), $extensions, true)) {
                return $type;
            }
        }

        return 'other';
    }

    protected function downloadFile(array $productFile)
    {
        $filePath = Pixel_product_files::FILE_BASE_DIR . $productFile['file'];

        if (!file_exists($filePath)) {
            $this->errors[] = $this->module->l('File does not exist on server', 'download');
            $this->redirectWithNotifications($this->context->link->getPageLink('404'));
            return;
        }

        $extension = strtolower(pathinfo($productFile['file'], PATHINFO_EXTENSION));
        $fileType = $this->getFileType($extension);

        // Déterminer le type MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        // Construire le nom du fichier à partir du nom produit + titre
        $parts = [];
        if (!empty($productFile['product_name'])) {
            $parts[] = $this->sanitizeFileName($productFile['product_name']);
        }
        if (!empty($productFile['title'])) {
            $parts[] = $this->sanitizeFileName($productFile['title']);
        }

        $fileName = $parts
            ? implode('-', $parts)
            : basename($productFile['file']);

        // Ajouter l'extension si nécessaire
        if (!preg_match('/\.' . preg_quote($extension, '/') . '$/', $fileName)) {
            $fileName .= '.' . $extension;
        }

        // Headers pour le téléchargement
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        // Nettoyage du buffer de sortie
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Lecture et envoi du fichier
        readfile($filePath);
        exit;
    }

    protected function sanitizeFileName($fileName)
    {
        // Remplacer les caractères spéciaux
        $fileName = str_replace(['"', "'", '/', '\\', '?', '*', ':', '|', '<', '>'], '-', $fileName);
        // Supprimer les espaces multiples
        $fileName = preg_replace('/\s+/', '-', $fileName);

        return trim($fileName);
    }
}
