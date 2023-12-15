<?php

namespace Pixel\Module\ProductFiles\Controller\Admin;

include_once _PS_MODULE_DIR_ . 'pixel_product_files/pixel_product_files.php';

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Language;
use Pixel\Module\ProductFiles\Entity\ProductFile;
use Pixel\Module\ProductFiles\Entity\ProductFileLang;
use pixel_product_files;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class ProductFileController extends FrameworkBundleAdminController
{
    /**
     * Save the file
     *
     * @param Request $request
     *
     * @return RedirectResponse
     * @throws ORMException
     */
    public function saveAction(Request $request): RedirectResponse
    {
        $referer = $request->headers->get('referer') . '#tab-hooks';
        if (!$referer) {
            return $this->redirectToRoute('admin_product_catalog');
        }

        $idProduct = $request->get('id_product');
        if (!$idProduct) {
            return $this->redirect($referer);
        }

        /** @var UploadedFile $file */
        foreach ($request->files as $file) {
            if (!$file) {
                continue;
            }
            if (!in_array(strtolower($file->getClientOriginalExtension()), pixel_product_files::ALLOWED_EXTENSIONS ?? [])) {
                $this->addFlash(
                    'error',
                    $this->trans('File extension is not allowed', 'Modules.Pixelproductfiles.Admin')
                );
                continue;
            }
            $fileName = uniqid() . '-' . $this->formatName($file->getClientOriginalName());
            $fileDirectory = pixel_product_files::FILE_BASE_DIR;

            if (!is_dir($fileDirectory)) {
                mkdir($fileDirectory, 0777, true);
            }
            $file->move($fileDirectory, $fileName);

            /** @var EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');

            $idLang = $request->get('id_lang') ? (int)$request->get('id_lang') : null;
            if ($request->get('all_languages')) {
                $idLang = null;
            }

            $idShop = $request->get('id_shop') ? (int)$request->get('id_shop') : null;
            if ($request->get('all_shops')) {
                $idShop = null;
            }

            $productFile = new ProductFile();
            $productFile
                ->setFile($fileName)
                ->setIdProduct($idProduct)
                ->setIdLang($idLang)
                ->setIdShop($idShop);
            $entityManager->persist($productFile);
            $entityManager->flush();

            $available = Language::getLanguages();
            foreach ($available as $language) {
                if ($productFile->getIdLang() && ($productFile->getIdLang() !== (int)$language['id_lang'])) {
                    continue;
                }
                $productFileLang = new ProductFileLang();
                $productFileLang
                    ->setIdFile($productFile->getId())
                    ->setIdLang((int)$language['id_lang'])
                    ->setTitle($request->get('title', ''))
                    ->setDescription($request->get('description', ''))
                    ->setPosition($request->get('position') ? (int)$request->get('position') : 0);
                $entityManager->persist($productFileLang);
                $entityManager->flush();
            }

            $this->addFlash(
                'success',
                $this->trans('The file has been added', 'Modules.Pixelproductfiles.Admin')
            );
        }

        return $this->redirect($referer);
    }

    /**
     * Delete the file
     *
     * @param Request $request
     * @return RedirectResponse
     * @throws ORMException
     */
    public function deleteAction(Request $request): RedirectResponse
    {
        $referer = $request->headers->get('referer') . '#tab-hooks';
        if (!$referer) {
            return $this->redirectToRoute('admin_product_catalog');
        }

        $idFile = $request->get('id_file');
        if (!$idFile) {
            return $this->redirect($referer);
        }

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $repository = $entityManager->getRepository(ProductFile::class);

        /** @var ProductFile $productFile */
        $productFile = $repository->findOneBy(['id' => $idFile]);
        if (!$productFile) {
            $this->addFlash(
                'error',
                $this->trans('File does not exist', 'Modules.Pixelproductfiles.Admin')
            );
            return $this->redirect($referer);
        }

        $filepath = pixel_product_files::FILE_BASE_DIR . $productFile->getFile();
        if (is_file($filepath)) {
            @unlink($filepath);
        }
        $entityManager->remove($productFile);
        $entityManager->flush();

        $this->addFlash(
            'success',
            $this->trans('The file has been deleted', 'Modules.Pixelproductfiles.Admin')
        );

        return $this->redirect($referer);
    }

    /**
     * Format file name
     *
     * @param string $value
     * @param string $replace
     *
     * @return string
     */
    protected function formatName(string $value, string $replace = '-'): string
    {
        $string = strtolower($value);
        $string = strtr($string, $this->getConvertTable());
        $string = preg_replace('#[^a-z0-9.]+#i', $replace, $string);

        return trim($string, $replace);
    }

    /**
     * Retrieve chars convert table
     *
     * @return string[]
     */
    protected function getConvertTable(): array
    {
        return [
            '&amp;' => 'and',   '@' => 'at',    '©' => 'c', '®' => 'r', 'À' => 'a',
            'Á' => 'a', 'Â' => 'a', 'Ä' => 'a', 'Å' => 'a', 'Æ' => 'ae','Ç' => 'c',
            'È' => 'e', 'É' => 'e', 'Ë' => 'e', 'Ì' => 'i', 'Í' => 'i', 'Î' => 'i',
            'Ï' => 'i', 'Ò' => 'o', 'Ó' => 'o', 'Ô' => 'o', 'Õ' => 'o', 'Ö' => 'o',
            'Ø' => 'o', 'Ù' => 'u', 'Ú' => 'u', 'Û' => 'u', 'Ü' => 'u', 'Ý' => 'y',
            'ß' => 'ss','à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a', 'å' => 'a',
            'æ' => 'ae','ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ò' => 'o', 'ó' => 'o',
            'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u',
            'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'þ' => 'p', 'ÿ' => 'y', 'Ā' => 'a',
            'ā' => 'a', 'Ă' => 'a', 'ă' => 'a', 'Ą' => 'a', 'ą' => 'a', 'Ć' => 'c',
            'ć' => 'c', 'Ĉ' => 'c', 'ĉ' => 'c', 'Ċ' => 'c', 'ċ' => 'c', 'Č' => 'c',
            'č' => 'c', 'Ď' => 'd', 'ď' => 'd', 'Đ' => 'd', 'đ' => 'd', 'Ē' => 'e',
            'ē' => 'e', 'Ĕ' => 'e', 'ĕ' => 'e', 'Ė' => 'e', 'ė' => 'e', 'Ę' => 'e',
            'ę' => 'e', 'Ě' => 'e', 'ě' => 'e', 'Ĝ' => 'g', 'ĝ' => 'g', 'Ğ' => 'g',
            'ğ' => 'g', 'Ġ' => 'g', 'ġ' => 'g', 'Ģ' => 'g', 'ģ' => 'g', 'Ĥ' => 'h',
            'ĥ' => 'h', 'Ħ' => 'h', 'ħ' => 'h', 'Ĩ' => 'i', 'ĩ' => 'i', 'Ī' => 'i',
            'ī' => 'i', 'Ĭ' => 'i', 'ĭ' => 'i', 'Į' => 'i', 'į' => 'i', 'İ' => 'i',
            'ı' => 'i', 'Ĳ' => 'ij','ĳ' => 'ij','Ĵ' => 'j', 'ĵ' => 'j', 'Ķ' => 'k',
            'ķ' => 'k', 'ĸ' => 'k', 'Ĺ' => 'l', 'ĺ' => 'l', 'Ļ' => 'l', 'ļ' => 'l',
            'Ľ' => 'l', 'ľ' => 'l', 'Ŀ' => 'l', 'ŀ' => 'l', 'Ł' => 'l', 'ł' => 'l',
            'Ń' => 'n', 'ń' => 'n', 'Ņ' => 'n', 'ņ' => 'n', 'Ň' => 'n', 'ň' => 'n',
            'ŉ' => 'n', 'Ŋ' => 'n', 'ŋ' => 'n', 'Ō' => 'o', 'ō' => 'o', 'Ŏ' => 'o',
            'ŏ' => 'o', 'Ő' => 'o', 'ő' => 'o', 'Œ' => 'oe','œ' => 'oe','Ŕ' => 'r',
            'ŕ' => 'r', 'Ŗ' => 'r', 'ŗ' => 'r', 'Ř' => 'r', 'ř' => 'r', 'Ś' => 's',
            'ś' => 's', 'Ŝ' => 's', 'ŝ' => 's', 'Ş' => 's', 'ş' => 's', 'Š' => 's',
            'š' => 's', 'Ţ' => 't', 'ţ' => 't', 'Ť' => 't', 'ť' => 't', 'Ŧ' => 't',
            'ŧ' => 't', 'Ũ' => 'u', 'ũ' => 'u', 'Ū' => 'u', 'ū' => 'u', 'Ŭ' => 'u',
            'ŭ' => 'u', 'Ů' => 'u', 'ů' => 'u', 'Ű' => 'u', 'ű' => 'u', 'Ų' => 'u',
            'ų' => 'u', 'Ŵ' => 'w', 'ŵ' => 'w', 'Ŷ' => 'y', 'ŷ' => 'y', 'Ÿ' => 'y',
            'Ź' => 'z', 'ź' => 'z', 'Ż' => 'z', 'ż' => 'z', 'Ž' => 'z', 'ž' => 'z',
            'ſ' => 'z', 'Ə' => 'e', 'ƒ' => 'f', 'Ơ' => 'o', 'ơ' => 'o', 'Ư' => 'u',
            'ư' => 'u', 'Ǎ' => 'a', 'ǎ' => 'a', 'Ǐ' => 'i', 'ǐ' => 'i', 'Ǒ' => 'o',
            'ǒ' => 'o', 'Ǔ' => 'u', 'ǔ' => 'u', 'Ǖ' => 'u', 'ǖ' => 'u', 'Ǘ' => 'u',
            'ǘ' => 'u', 'Ǚ' => 'u', 'ǚ' => 'u', 'Ǜ' => 'u', 'ǜ' => 'u', 'Ǻ' => 'a',
            'ǻ' => 'a', 'Ǽ' => 'ae','ǽ' => 'ae','Ǿ' => 'o', 'ǿ' => 'o', 'ə' => 'e',
            'Ё' => 'jo','Є' => 'e', 'І' => 'i', 'Ї' => 'i', 'А' => 'a', 'Б' => 'b',
            'В' => 'v', 'Г' => 'g', 'Д' => 'd', 'Е' => 'e', 'Ж' => 'zh','З' => 'z',
            'И' => 'i', 'Й' => 'j', 'К' => 'k', 'Л' => 'l', 'М' => 'm', 'Н' => 'n',
            'О' => 'o', 'П' => 'p', 'Р' => 'r', 'С' => 's', 'Т' => 't', 'У' => 'u',
            'Ф' => 'f', 'Х' => 'h', 'Ц' => 'c', 'Ч' => 'ch','Ш' => 'sh','Щ' => 'sch',
            'Ъ' => '-', 'Ы' => 'y', 'Ь' => '-', 'Э' => 'je','Ю' => 'ju','Я' => 'ja',
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e',
            'ж' => 'zh','з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l',
            'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's',
            'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
            'ш' => 'sh','щ' => 'sch','ъ' => '-','ы' => 'y', 'ь' => '-', 'э' => 'je',
            'ю' => 'ju','я' => 'ja','ё' => 'jo','є' => 'e', 'і' => 'i', 'ї' => 'i',
            'Ґ' => 'g', 'ґ' => 'g', 'א' => 'a', 'ב' => 'b', 'ג' => 'g', 'ד' => 'd',
            'ה' => 'h', 'ו' => 'v', 'ז' => 'z', 'ח' => 'h', 'ט' => 't', 'י' => 'i',
            'ך' => 'k', 'כ' => 'k', 'ל' => 'l', 'ם' => 'm', 'מ' => 'm', 'ן' => 'n',
            'נ' => 'n', 'ס' => 's', 'ע' => 'e', 'ף' => 'p', 'פ' => 'p', 'ץ' => 'C',
            'צ' => 'c', 'ק' => 'q', 'ר' => 'r', 'ש' => 'w', 'ת' => 't', '™' => 'tm',
        ];
    }
}
