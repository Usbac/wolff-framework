<?php

namespace Wolff\Utils;

final class Pagination
{

    const PLACEHOLDER = '{page}';

    /**
     * The total of elements
     *
     * @var int
     */
    private $total;

    /**
     * The total of elements per page
     *
     * @var int
     */
    private $per_page;

    /**
     * The current page
     *
     * @var int
     */
    private $page;

    /**
     * The number of pages that will be around the current page
     *
     * @var int
     */
    private $side_pages_n;

    /**
     * The URL format of the pages
     *
     * @var string
     */
    private $url_format;

    /**
     * Show or not the first and last page
     *
     * @var bool
     */
    private $show_ends;


    /**
     * Constructor
     *
     * @param  int  $total  the total of elements
     * @param  int  $per_page  the total of elements per page
     * @param  int  $page  the current page
     * @param  int  $side_pages_n  the number of pages that will
     * be beside the current page
     * @param  string  $url_format  the url format
     */
    public function __construct(
        int $total = 0,
        int $per_page = 0,
        int $page = 0,
        int $side_pages_n = 5,
        string $url_format = self::PLACEHOLDER
    ) {
        $this->total = $total;
        $this->per_page = $per_page;
        $this->page = $page;
        $this->side_pages_n = $side_pages_n;
        $this->url_format = $url_format;
        $this->show_ends = true;
    }


    /**
     * Returns the pagination array
     *
     * @return array the pagination array
     */
    public function get(): array
    {
        $total_pages = ceil($this->total / $this->per_page);
        $begin = $this->page - $this->side_pages_n;
        $end = $this->page + $this->side_pages_n;
        $pagination = [];

        if ($begin <= 0) {
            $begin = 1;
        }

        for ($i = $begin; $i <= $end && $i <= $total_pages; $i++) {
            $pagination[] = $this->getNewPage($i);
        }

        if ($this->show_ends) {
            $this->addEnds($pagination);
        }

        return $pagination;
    }


    /**
     * Sets the total number of elements
     *
     * @param  int  $total  the total number of elements
     *
     * @return Pagination this
     */
    public function setTotal(int $total): Pagination
    {
        $this->total = $total;
        return $this;
    }


    /**
     * Sets the number of elements per page
     *
     * @param  int  $per_page  the number of elements per page
     *
     * @return Pagination this
     */
    public function setPageSize(int $per_page): Pagination
    {
        $this->per_page = $per_page;
        return $this;
    }


    /**
     * Sets the current page
     *
     * @param  int  $page  the current page
     *
     * @return Pagination this
     */
    public function setPage(int $page = 0): Pagination
    {
        $this->page = $page;
        return $this;
    }


    /**
     * Set the number of pages that will be around the current page
     *
     * @param  int  $side_pages_n  the number of pages that will
     * be beside the current page
     *
     * @return Pagination this
     */
    public function setSidePages(int $side_pages_n = 5): Pagination
    {
        $this->side_pages_n = $side_pages_n;
        return $this;
    }


    /**
     * Sets the pages url
     * The placeholder for the page number in the string
     * must have the format: {page}
     *
     * @param  string  $url_format  the pages url
     *
     * @return Pagination this
     */
    public function setUrl(string $url_format): Pagination
    {
        $this->url_format = $url_format;
        return $this;
    }


    /**
     * Sets if the first and last page will be shown
     *
     * @param  bool  $show_ends  true for showing the first
     * and last page in the pagination, false for not showing it
     *
     * @return Pagination this
     */
    public function showEnds(bool $show_ends = true): Pagination
    {
        $this->show_ends = $show_ends;
        return $this;
    }


    /**
     * Adds the first and last page to the pagination array
     *
     * @param  array  &$pagination  the pagination array
     */
    private function addEnds(array &$pagination): void
    {
        if (!isset($pagination[0])) {
            return;
        }

        if ($pagination[0]['index'] != 1) {
            array_unshift($pagination, $this->getNewPage(1));
        }

        $total_pages = ceil($this->total / $this->per_page);

        if (end($pagination)['index'] != $total_pages) {
            $pagination[] = $this->getNewPage($total_pages);
        }
    }


    /**
     * Returns a new page based in the given index
     *
     * @param  int  $i  the page index
     *
     * @return array a new page based in the given index
     */
    private function getNewPage(int $i): array
    {
        return [
            'index'        => $i,
            'current_page' => $i === $this->page,
            'url'          => str_replace(self::PLACEHOLDER, $i, $this->url_format)
        ];
    }
}
