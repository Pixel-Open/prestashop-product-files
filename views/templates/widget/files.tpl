{if $files}
    <ul class="pxl-product-files">
        {foreach from=$files item=file}
            {assign var="extension" value=$file->getFile()|pathinfo:$smarty.const.PATHINFO_EXTENSION}
            <li class="product-file">
                <a href="{$link->getModuleLink("pixel_product_files", "download", ['id_file' => $file->getId()])}" class="product-file-link">
                    {if isset($icons[$extension])}
                        <img src="{$path.icons}{$icons[$extension]}" alt="{$file->getTitle()}" />
                    {/if}
                    <span class="product-file-title">{if $file->getTitle()}{$file->getTitle()}{else}{$file->getFile()}{/if}</span>
                </a>
                {if $file->getDescription()}
                    <span class="product-file-description">
                        {$file->getDescription()}
                    </span>
                {/if}
            </li>
        {/foreach}
    </ul>
{/if}
