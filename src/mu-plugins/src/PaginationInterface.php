<?php

interface PaginationInterface {
	
	const DEFAULT_PAGINATION_SPREAD = 2;

	public function pagination($total=-1, $spread=-1);

}
